<?php
// Link / unlink an Excel part number to a master part number (m_production_numbers.stock_part_number).
// POST JSON:
//   { action: "link",   production_number_id, stock_part_number, overwrite }
//   { action: "unlink", production_number_id }
// "link" answers status "confirm" (and changes nothing) when the master already has another
// Excel part number and overwrite is not true.
require_once __DIR__ . '/common.php';

$b = json_decode(file_get_contents('php://input'), true);
if (!is_array($b)) pm_fail('bad request');
$action = $b['action'] ?? '';
$id = (int)($b['production_number_id'] ?? 0);
if ($id <= 0) pm_fail('品番を選んでください');

try {
  $pdo = getPDO();
  $st = $pdo->prepare("SELECT id, production_number, stock_part_number FROM m_production_numbers WHERE id = :id");
  $st->execute([':id' => $id]);
  $m = $st->fetch(PDO::FETCH_ASSOC);
  if (!$m) pm_fail('マスタに無い品番です');

  if ($action === 'unlink') {
    $pdo->prepare("UPDATE m_production_numbers SET stock_part_number = NULL WHERE id = :id")->execute([':id' => $id]);
    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  if ($action !== 'link') pm_fail('bad action');

  $spn = trim((string)($b['stock_part_number'] ?? ''), " \t\n\r\0\x0B\u{3000}");
  if ($spn === '') pm_fail('Excel の品番が空です');
  if (mb_strlen($spn) > 100) pm_fail('Excel の品番は100文字までです');
  $k = pm_key($spn);

  // the Excel part number must not already be a master part number, nor linked to another master
  foreach ($pdo->query("SELECT id, production_number, stock_part_number FROM m_production_numbers") as $r) {
    if (pm_key($r['production_number']) === $k) pm_fail("「{$spn}」はマスタにある品番です（対応表は要りません）");
    if ((int)$r['id'] !== $id && $r['stock_part_number'] !== null && pm_key($r['stock_part_number']) === $k) {
      pm_fail("「{$spn}」は、もう {$r['production_number']} につながっています");
    }
  }
  $cur = $m['stock_part_number'];
  if ($cur !== null && $cur !== '' && pm_key($cur) !== $k && empty($b['overwrite'])) {
    echo json_encode(['status' => 'confirm', 'current' => $cur, 'production_number' => $m['production_number']], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $pdo->prepare("UPDATE m_production_numbers SET stock_part_number = :s WHERE id = :id")->execute([':s' => $spn, ':id' => $id]);
  echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  pm_fail($e->getMessage());
}
