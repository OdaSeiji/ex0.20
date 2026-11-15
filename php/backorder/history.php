<?php
// Change history of one order (shipping instruction no.), newest first.
// GET: no=<shipping instruction no.>
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$no = trim((string)($_GET['no'] ?? ''));
if ($no === '') {
  echo json_encode(['status' => 'error', 'message' => 'no is required']);
  exit;
}
try {
  $st = getPDO()->prepare(
    "SELECT DATE_FORMAT(l.changed_at, '%Y-%m-%d %H:%i') AS changed_at,
            s2.staff_name AS changed_by_name, s1.staff_name AS staff_name,
            DATE_FORMAT(l.planned_date, '%Y-%m-%d') AS planned_date, l.comment
     FROM t_backorder_followup_log l
     JOIN t_backorder_followup f ON f.id = l.followup_id
     LEFT JOIN m_staff s1 ON s1.id = l.staff_id
     LEFT JOIN m_staff s2 ON s2.id = l.changed_by
     WHERE f.shipping_instruction_no = ?
     ORDER BY l.changed_at DESC, l.id DESC");
  $st->execute([$no]);
  echo json_encode(['status' => 'ok', 'log' => $st->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
