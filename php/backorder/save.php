<?php
// Save the person in charge / delivery date / comment of one order (shipping instruction no.).
// POST JSON: { shipping_instruction_no, production_number, staff_id, planned_date, comment, changed_by }
// Inserts or updates t_backorder_followup, and adds a row to t_backorder_followup_log when something changed.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

function fail($msg) {
  echo json_encode(['status' => 'error', 'message' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$b = json_decode(file_get_contents('php://input'), true);
if (!is_array($b)) fail('bad request');

$no = trim((string)($b['shipping_instruction_no'] ?? ''));
$pn = trim((string)($b['production_number'] ?? ''));
$staffId = ($b['staff_id'] ?? '') === '' || $b['staff_id'] === null ? null : (int)$b['staff_id'];
$date = trim((string)($b['planned_date'] ?? ''));
$comment = trim((string)($b['comment'] ?? ''));
$changedBy = (int)($b['changed_by'] ?? 0);

if ($no === '' || mb_strlen($no) > 50) fail('出荷指示番号が不正です');
if ($pn === '' || mb_strlen($pn) > 100) fail('品番が不正です');
if ($date !== '' && !(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4)))) {
  fail('納期の日付が不正です');
}
if (mb_strlen($comment) > 1000) fail('コメントは1000文字までです');
if ($changedBy <= 0) fail('入力者を選んでください');
$date = $date === '' ? null : $date;
$comment = $comment === '' ? null : $comment;

try {
  $pdo = getPDO();
  // both people must exist and not have left
  $chk = $pdo->prepare("SELECT COUNT(*) FROM m_staff WHERE id = ? AND (leave_at IS NULL OR leave_at > CURDATE())");
  foreach (array_filter([$staffId, $changedBy]) as $sid) {
    $chk->execute([$sid]);
    if (!$chk->fetchColumn()) fail('スタッフが見つかりません（id ' . $sid . '）');
  }

  $pdo->beginTransaction();
  $st = $pdo->prepare("SELECT id, staff_id, planned_date, comment FROM t_backorder_followup WHERE shipping_instruction_no = ? FOR UPDATE");
  $st->execute([$no]);
  $cur = $st->fetch();

  $changed = true;
  if ($cur) {
    $changed = !((string)$cur['staff_id'] === (string)$staffId
      && (string)$cur['planned_date'] === (string)$date
      && (string)$cur['comment'] === (string)$comment);
    if ($changed) {
      $pdo->prepare("UPDATE t_backorder_followup SET staff_id = ?, planned_date = ?, comment = ?, updated_by = ? WHERE id = ?")
          ->execute([$staffId, $date, $comment, $changedBy, $cur['id']]);
    }
    $id = (int)$cur['id'];
  } else {
    $pdo->prepare("INSERT INTO t_backorder_followup (shipping_instruction_no, production_number, staff_id, planned_date, comment, updated_by) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$no, $pn, $staffId, $date, $comment, $changedBy]);
    $id = (int)$pdo->lastInsertId();
  }
  if ($changed) {
    $pdo->prepare("INSERT INTO t_backorder_followup_log (followup_id, staff_id, planned_date, comment, changed_by) VALUES (?, ?, ?, ?, ?)")
        ->execute([$id, $staffId, $date, $comment, $changedBy]);
  }
  $pdo->commit();

  $st = $pdo->prepare(
    "SELECT f.shipping_instruction_no, f.staff_id, s1.staff_name AS staff_name,
            DATE_FORMAT(f.planned_date, '%Y-%m-%d') AS planned_date, f.comment,
            f.updated_by, s2.staff_name AS updated_by_name,
            DATE_FORMAT(f.updated_at, '%Y-%m-%d %H:%i') AS updated_at
     FROM t_backorder_followup f
     LEFT JOIN m_staff s1 ON s1.id = f.staff_id
     LEFT JOIN m_staff s2 ON s2.id = f.updated_by
     WHERE f.id = ?");
  $st->execute([$id]);
  echo json_encode(['status' => 'ok', 'changed' => $changed, 'followup' => $st->fetch()], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  fail($e->getMessage());
}
