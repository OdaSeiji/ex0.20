<?php
// Part number map: Excel part number (inventory / order) -> m_production_numbers
//   linked when the Excel part number equals production_number, or equals stock_part_number
//   (compared ignoring surrounding spaces and upper/lower case, same as the stock shortage page)
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

function pm_key($s) { return strtoupper(trim((string)$s, " \t\n\r\0\x0B\u{3000}")); }

function pm_fail($msg) {
  echo json_encode(['status' => 'error', 'message' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

// text before the last hyphen ("ABC-123-30K" -> "ABC-123"), or null when there is no hyphen
function pm_head($k) {
  $p = strrpos($k, '-');
  return $p === false || $p === 0 ? null : substr($k, 0, $p);
}
