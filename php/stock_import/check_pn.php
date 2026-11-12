<?php
// Returns the part numbers that are NOT in m_production_numbers.
// POST JSON: { "production_numbers": ["A-1", "B-2", ...] }  →  { "status": "ok", "unknown": ["B-2"] }
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/common.php';

$body = json_decode(file_get_contents('php://input'), true);
$list = $body['production_numbers'] ?? null;
if (!is_array($list)) {
  echo json_encode(['status' => 'error', 'message' => 'bad request']);
  exit;
}

try {
  $known = known_pn_set(getPDO());
  $unknown = [];
  foreach ($list as $pn) {
    if (!isset($known[pn_key($pn)])) $unknown[] = (string)$pn;
  }
  echo json_encode(['status' => 'ok', 'unknown' => array_values(array_unique($unknown))], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
