<?php
// Imported dates and row counts of the two staging tables (latest 30 dates each).
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

try {
  $pdo = getPDO();
  $out = [];
  foreach (['inventory' => 'stg_inventory_snapshot', 'shipping' => 'stg_shipping_instruction'] as $key => $table) {
    $out[$key] = $pdo->query(
      "SELECT DATE_FORMAT(source_file_date, '%Y-%m-%d') AS source_file_date,
              COUNT(*) AS cnt,
              DATE_FORMAT(MAX(created_at), '%Y-%m-%d %H:%i') AS imported_at
       FROM $table
       GROUP BY source_file_date
       ORDER BY source_file_date DESC
       LIMIT 30"
    )->fetchAll();
  }
  echo json_encode(['status' => 'ok'] + $out, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
