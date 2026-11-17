<?php
// All orders of the latest order Excel (stg_shipping_instruction, latest source_file_date):
// every AB status, shipped or not, with the backorder follow-up (person in charge / delivery date / comment).
// Used by the download button "受注 全件" on stock_shortage.html.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

try {
  $pdo = getPDO();
  $shipDate = $pdo->query("SELECT DATE_FORMAT(MAX(source_file_date), '%Y-%m-%d') FROM stg_shipping_instruction")->fetchColumn() ?: null;
  $rows = [];
  if ($shipDate) {
    $st = $pdo->prepare("
      SELECT s.production_number, s.shipping_instruction_no, TRIM(s.ab_status) AS ab_status, s.quantity,
             DATE_FORMAT(s.order_date, '%Y-%m-%d') AS order_date,
             DATE_FORMAT(s.due_date, '%Y-%m-%d') AS due_date,
             DATE_FORMAT(s.reply_date, '%Y-%m-%d') AS reply_date,
             DATE_FORMAT(s.shipping_date, '%Y-%m-%d') AS shipping_date,
             s.customer_name, s.country, s.transportation_method,
             s1.staff_name, DATE_FORMAT(f.planned_date, '%Y-%m-%d') AS planned_date, f.comment,
             s2.staff_name AS updated_by_name, DATE_FORMAT(f.updated_at, '%Y-%m-%d %H:%i') AS updated_at
      FROM stg_shipping_instruction s
      LEFT JOIN t_backorder_followup f ON f.shipping_instruction_no = s.shipping_instruction_no
      LEFT JOIN m_staff s1 ON s1.id = f.staff_id
      LEFT JOIN m_staff s2 ON s2.id = f.updated_by
      WHERE s.source_file_date = :d
      ORDER BY s.production_number, s.due_date, s.shipping_instruction_no, s.id
    ");
    $st->execute([':d' => $shipDate]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) $r['quantity'] = $r['quantity'] === null ? null : (int)$r['quantity'];
    unset($r);
  }
  echo json_encode(['status' => 'ok', 'shipping_date' => $shipDate, 'rows' => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
