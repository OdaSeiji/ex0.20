<?php
// Search the part number master (for linking an Excel part number by hand).
// GET: q (part of the part number, at least 2 characters)
require_once __DIR__ . '/common.php';

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) {
  echo json_encode(['status' => 'ok', 'rows' => []]);
  exit;
}
try {
  $pdo = getPDO();
  $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
  $st = $pdo->prepare("
    SELECT id, production_number, stock_part_number
    FROM m_production_numbers
    WHERE production_number LIKE :q
    ORDER BY production_number
    LIMIT 30
  ");
  $st->execute([':q' => $like]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as &$r) $r['id'] = (int)$r['id'];
  echo json_encode(['status' => 'ok', 'rows' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  pm_fail($e->getMessage());
}
