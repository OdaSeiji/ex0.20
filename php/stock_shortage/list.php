<?php
// Stock shortage: A+B order quantity per part number vs. unallocated stock.
// - Orders: stg_shipping_instruction, latest source_file_date, ab_status A or B,
//   only orders NOT shipped yet (shipping_date = Excel J is empty).
//   Which orders count for the month is decided by `basis`:
//     due     = customer deadline (Excel D) up to the end of the month, past unshipped ones included (default)
//     planned = the delivery date entered by the person in charge (t_backorder_followup.planned_date) in the month
// - Stock:  stg_inventory_snapshot, latest source_file_date.
// GET: month=YYYY-MM, basis=due|planned
ini_set('display_errors', '0');
// Inventory and orders are matched by part number ignoring surrounding spaces and upper/lower case.
function pn_key($s) { return strtoupper(trim((string)$s, " \t\n\r\0\x0B\u{3000}")); }
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^(\d{4})-(\d{2})$/', $month, $m) || (int)$m[2] < 1 || (int)$m[2] > 12) {
  echo json_encode(['status' => 'error', 'message' => 'bad month']);
  exit;
}
$basis = ($_GET['basis'] ?? 'due') === 'planned' ? 'planned' : 'due';

$from = sprintf('%04d-%02d-01', (int)$m[1], (int)$m[2]);
$to = date('Y-m-d', strtotime("$from +1 month"));

// date column and condition for the basis (fixed strings, no user input)
if ($basis === 'due') {
  $dateCol = 's.due_date';
  $dateCond = 's.due_date < :to';                              // up to the end of the month, past ones included
  $params = fn($d) => [':d' => $d, ':to' => $to];
} else {
  $dateCol = 'f.planned_date';
  $dateCond = 'f.planned_date >= :from AND f.planned_date < :to';
  $params = fn($d) => [':d' => $d, ':from' => $from, ':to' => $to];
}

try {
  $pdo = getPDO();
  $invDate = $pdo->query("SELECT DATE_FORMAT(MAX(source_file_date), '%Y-%m-%d') FROM stg_inventory_snapshot")->fetchColumn() ?: null;
  $shipDate = $pdo->query("SELECT DATE_FORMAT(MAX(source_file_date), '%Y-%m-%d') FROM stg_shipping_instruction")->fetchColumn() ?: null;

  $from_join = "
      FROM stg_shipping_instruction s
      LEFT JOIN t_backorder_followup f ON f.shipping_instruction_no = s.shipping_instruction_no";

  $orders = [];      // per part number: totals
  $orderLines = [];  // per part number: the orders
  $notes = ['no_date' => 0, 'other_ab' => 0];
  if ($shipDate) {
    $st = $pdo->prepare("
      SELECT s.production_number, s.shipping_instruction_no, s.quantity, UPPER(TRIM(s.ab_status)) AS ab_status,
             DATE_FORMAT(s.order_date, '%Y-%m-%d') AS order_date,
             DATE_FORMAT(s.due_date, '%Y-%m-%d') AS due_date,
             s.customer_name, s.country, s.transportation_method,
             f.staff_id, s1.staff_name, DATE_FORMAT(f.planned_date, '%Y-%m-%d') AS planned_date, f.comment,
             s2.staff_name AS updated_by_name, DATE_FORMAT(f.updated_at, '%Y-%m-%d %H:%i') AS updated_at
      $from_join
      LEFT JOIN m_staff s1 ON s1.id = f.staff_id
      LEFT JOIN m_staff s2 ON s2.id = f.updated_by
      WHERE s.source_file_date = :d
        AND s.shipping_date IS NULL
        AND $dateCond
        AND UPPER(TRIM(s.ab_status)) IN ('A', 'B')
      ORDER BY $dateCol, s.due_date, s.shipping_instruction_no
    ");
    $st->execute($params($shipDate));
    foreach ($st->fetchAll() as $r) {
      $r['quantity'] = (int)$r['quantity'];
      $k = pn_key($r['production_number']);
      $orderLines[$k][] = $r;
      $o = &$orders[$k];
      $o['production_number'] = $o['production_number'] ?? trim($r['production_number']);
      $o['a_qty'] = ($o['a_qty'] ?? 0) + ($r['ab_status'] === 'A' ? $r['quantity'] : 0);
      $o['b_qty'] = ($o['b_qty'] ?? 0) + ($r['ab_status'] === 'B' ? $r['quantity'] : 0);
      $o['a_cnt'] = ($o['a_cnt'] ?? 0) + ($r['ab_status'] === 'A' ? 1 : 0);
      $o['b_cnt'] = ($o['b_cnt'] ?? 0) + ($r['ab_status'] === 'B' ? 1 : 0);
      unset($o);
    }

    // Rows we leave out (shown as notes): unshipped A/B orders with no date for the basis, or AB other than A/B
    $st = $pdo->prepare("SELECT COUNT(*) $from_join WHERE s.source_file_date = :d AND s.shipping_date IS NULL
                         AND UPPER(TRIM(s.ab_status)) IN ('A', 'B') AND $dateCol IS NULL");
    $st->execute([':d' => $shipDate]);
    $notes['no_date'] = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT COUNT(*) $from_join WHERE s.source_file_date = :d AND s.shipping_date IS NULL AND $dateCond
                         AND (s.ab_status IS NULL OR UPPER(TRIM(s.ab_status)) NOT IN ('A', 'B'))");
    $st->execute($params($shipDate));
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
    foreach ($st->fetchAll() as $r) {
      $k = pn_key($r['production_number']);
      if (isset($stock[$k])) {
        foreach (['stock_qty', 'allocated_qty', 'unallocated_qty'] as $f) $stock[$k][$f] += $r[$f];
      } else {
        $stock[$k] = $r;
      }
    }
  }

  // One row per part number that has A/B orders
  $rows = [];
  foreach ($orders as $k => $o) {
    $s = $stock[$k] ?? null;
    $a = (int)$o['a_qty'];
    $b = (int)$o['b_qty'];
    $un = $s ? (int)$s['unallocated_qty'] : 0;
    $rows[] = [
      'production_number' => $o['production_number'],
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
      'orders' => $orderLines[$k] ?? [],
      // orders that still have no person in charge or no delivery date
      'open_cnt' => count(array_filter($orderLines[$k] ?? [], fn($l) => !$l['staff_id'] || !$l['planned_date'])),
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
