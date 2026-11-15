<?php
// Staff who have not left (for the "person in charge" and "entered by" pull-downs)
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

try {
  $rows = getPDO()->query(
    "SELECT id, staff_name, emploee_number
     FROM m_staff
     WHERE leave_at IS NULL OR leave_at > CURDATE()
     ORDER BY staff_name, id"
  )->fetchAll();
  echo json_encode(['status' => 'ok', 'staff' => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
