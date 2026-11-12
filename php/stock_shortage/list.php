<?php
// Stock shortage: this month's A+B order quantity per part number vs. unallocated stock.
// - Orders: stg_shipping_instruction, latest source_file_date, ab_status A or B,
//   month decided by `basis` (reply_date by default — provisional, waiting for the manufacturing dept).
// - Stock:  stg_inventory_snapshot, latest source_file_date.
// GET: month=YYYY-MM, basis=reply_date|due_date|order_date
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^(\d{4})-(\d{2})$/', $month, $m) || (int)$m[2] < 1 || (int)$m[2] > 12) {
  echo json_encode(['status' => 'error', 'message' => 'bad month']);
  exit;
}
$basis = $_GET['basis'] ?? 'reply_date';
if (!in_array($basis, ['reply_date', 'due_date', 'order_date'], true)) $basis = 'reply_date';

$from = sprintf('%04d-%02d-01', (int)$m[1], (int)$m[2]);
$to = date('Y-m-d', strtotime("$from +1 month"));

try {
  $pdo = getPDO();
  $invDate = $pdo->query("SELECT DATE_FORMAT(MAX(source_file_date), '%Y-%m-%d') FROM stg_inventory_snapshot")->fetchColumn() ?: null;
  $shipDate = $pdo->query("SELECT DATE_FORMAT(MAX(source_file_date), '%Y-%m-%d') FROM stg_shipping_instruction")->fetchColumn() ?: null;

  $orders = [];
  $notes = ['no_date' => 0, 'other_ab' => 0];
  if ($shipDate) {
    // A / B per part number in the month ($basis is from the whitelist above)
    $st = $pdo->prepare("
      SELECT production_number,
             SUM(CASE WHEN UPPER(TRIM(ab_status)) = 'A' THEN quantity ELSE 0 END) AS a_qty,
             SUM(CASE WHEN UPPER(TRIM(ab_status)) = 'B' THEN quantity ELSE 0 END) AS b_qty,
             SUM(UPPER(TRIM(ab_status)) = 'A') AS a_cnt,
             SUM(UPPER(TRIM(ab_status)) = 'B') AS b_cnt,
             DATE_FORMAT(MIN($basis), '%Y-%m-%d') AS first_date
      FROM stg_shipping_instruction
      WHERE source_file_date = :d
        AND $basis >= :from AND $basis < :to
        AND UPPER(TRIM(ab_status)) IN ('A', 'B')
      GROUP BY production_number
    ");
    $st->execute([':d' => $shipDate, ':from' => $from, ':to' => $to]);
    foreach ($st->fetchAll() as $r) $orders[$r['production_number']] = $r;

    // Rows we leave out (shown as notes): no date for the basis, or AB other than A/B (in the month)
    $st = $pdo->prepare("SELECT COUNT(*) FROM stg_shipping_instruction WHERE source_file_date = :d AND $basis IS NULL");
    $st->execute([':d' => $shipDate]);
    $notes['no_date'] = (int)$st->fetchColumn();
    $st = $pdo->prepare("
      SELECT COUNT(*) FROM stg_shipping_instruction
      WHERE source_file_date = :d AND $basis >= :from AND $basis < :to
        AND (ab_status IS NULL OR UPPER(TRIM(ab_status)) NOT IN ('A', 'B'))");
    $st->execute([':d' => $shipDate, ':from' => $from, ':to' => $to]);
    $notes['other_ab'] = (int)$st->fetchColumn();
  }

  $stock = [];
  if ($invDate) {
    $st = $pdo->prepare("
      SELECT production_number,
             SUM(stock_quantity) AS stock_qty,
             SUM(allocated_quantity) AS allocated_qty,
             SUM(unallocated_inventory) AS unallocated_qty
      FROM stg_inventory_snapshot
      WHERE source_file_date = :d
      GROUP BY production_number
    ");
    $st->execute([':d' => $invDate]);
    foreach ($st->fetchAll() as $r) $stock[$r['production_number']] = $r;
  }

  // One row per part number that has A/B orders in the month
  $rows = [];
  foreach ($orders as $pn => $o) {
    $s = $stock[$pn] ?? null;
    $a = (int)$o['a_qty'];
    $b = (int)$o['b_qty'];
    $un = $s ? (int)$s['unallocated_qty'] : 0;
    $rows[] = [
      'production_number' => $pn,
      'in_stock_file' => $s !== null,
      'stock_qty' => $s ? (int)$s['stock_qty'] : null,
      'allocated_qty' => $s ? (int)$s['allocated_qty'] : null,
      'unallocated_qty' => $un,
      'a_qty' => $a,
      'b_qty' => $b,
      'a_cnt' => (int)$o['a_cnt'],
      'b_cnt' => (int)$o['b_cnt'],
      'ab_qty' => $a + $b,
      'balance' => $un - ($a + $b),
      'first_date' => $o['first_date'],
    ];
  }
  usort($rows, fn($x, $y) => $x['balance'] <=> $y['balance'] ?: strcmp($x['production_number'], $y['production_number']));

  echo json_encode([
    'status' => 'ok',
    'month' => sprintf('%04d-%02d', (int)$m[1], (int)$m[2]),
    'basis' => $basis,
    'inventory_date' => $invDate,
    'shipping_date' => $shipDate,
    'notes' => $notes,
    'rows' => $rows,
  ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
