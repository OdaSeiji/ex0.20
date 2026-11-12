<?php
// Excel import into the staging tables (stg_inventory_snapshot / stg_shipping_instruction).
// The browser reads the Excel file (lib/xlsx.js), maps the columns and sends the rows as JSON.
// Here we check the rows again, then delete the rows of the same source_file_date and insert (one transaction).
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

function fail($msg, $errors = []) {
  echo json_encode(['status' => 'error', 'message' => $msg, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) fail('bad request');

$type = $body['type'] ?? '';
$fileDate = $body['source_file_date'] ?? '';
$rows = $body['rows'] ?? null;

if (!in_array($type, ['inventory', 'shipping'], true)) fail('取込種別が不正です');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fileDate) || !checkdate((int)substr($fileDate, 5, 2), (int)substr($fileDate, 8, 2), (int)substr($fileDate, 0, 4))) {
  fail('取込日付が不正です');
}
if (!is_array($rows) || count($rows) === 0) fail('取込対象データがありません');

$isDate = function ($v) {
  if ($v === null || $v === '') return true;
  return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)
    && checkdate((int)substr($v, 5, 2), (int)substr($v, 8, 2), (int)substr($v, 0, 4));
};
$str = function ($v, $max) {
  if ($v === null) return null;
  $s = trim((string)$v);
  if ($s === '') return null;
  return mb_substr($s, 0, $max);
};

$errors = [];
$clean = [];
foreach ($rows as $r) {
  $line = (int)($r['line'] ?? 0);
  $pn = trim((string)($r['production_number'] ?? ''));
  if ($pn === '') { $errors[] = ['line' => $line, 'message' => '品番が空です']; continue; }
  if (mb_strlen($pn) > 100) { $errors[] = ['line' => $line, 'message' => '品番が長すぎます']; continue; }

  if ($type === 'inventory') {
    $q = $r['unallocated_inventory'] ?? 0;
    if (!is_int($q)) { $errors[] = ['line' => $line, 'message' => '未引当在庫数が整数ではありません']; continue; }
    $clean[] = [$fileDate, $pn, $q];
  } else {
    $q = $r['quantity'] ?? 0;
    if (!is_int($q)) { $errors[] = ['line' => $line, 'message' => '数量が整数ではありません']; continue; }
    foreach (['order_date', 'due_date', 'reply_date'] as $k) {
      if (!$isDate($r[$k] ?? null)) { $errors[] = ['line' => $line, 'message' => '日付形式が不正です']; continue 2; }
    }
    $clean[] = [
      $fileDate, $pn, $q,
      ($r['order_date'] ?? '') ?: null,
      ($r['due_date'] ?? '') ?: null,
      $str($r['customer_name'] ?? null, 255),
      $str($r['shipping_instruction_no'] ?? null, 50),
      $str($r['transportation_method'] ?? null, 20),
      ($r['reply_date'] ?? '') ?: null,
      $str($r['ab_status'] ?? null, 10),
      $str($r['custom_status'] ?? null, 50),
    ];
  }
}
if ($errors) fail('取込失敗', $errors);

if ($type === 'inventory') {
  $table = 'stg_inventory_snapshot';
  $cols = '(source_file_date, production_number, unallocated_inventory)';
  $n = 3;
} else {
  $table = 'stg_shipping_instruction';
  $cols = '(source_file_date, production_number, quantity, order_date, due_date, customer_name,'
        . ' shipping_instruction_no, transportation_method, reply_date, ab_status, custom_status)';
  $n = 11;
}

try {
  $pdo = getPDO();
  $pdo->beginTransaction();
  $del = $pdo->prepare("DELETE FROM $table WHERE source_file_date = ?");
  $del->execute([$fileDate]);
  $deleted = $del->rowCount();

  // insert in chunks of 500 rows
  $ph = '(' . implode(',', array_fill(0, $n, '?')) . ')';
  foreach (array_chunk($clean, 500) as $chunk) {
    $sql = "INSERT INTO $table $cols VALUES " . implode(',', array_fill(0, count($chunk), $ph));
    $params = [];
    foreach ($chunk as $c) foreach ($c as $v) $params[] = $v;
    $pdo->prepare($sql)->execute($params);
  }
  $pdo->commit();
  echo json_encode(['status' => 'ok', 'inserted' => count($clean), 'deleted' => $deleted], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  fail($e->getMessage());
}
