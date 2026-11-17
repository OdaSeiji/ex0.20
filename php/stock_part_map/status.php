<?php
// Check that every part number of the latest inventory / order Excel is linked to the master.
// GET (no parameters)
// Returns: dates, counts per file, unmatched part numbers with candidates, the current map (stock_part_number rows)
require_once __DIR__ . '/common.php';

try {
  $pdo = getPDO();
  $invDate = $pdo->query("SELECT DATE_FORMAT(MAX(source_file_date), '%Y-%m-%d') FROM stg_inventory_snapshot")->fetchColumn() ?: null;
  $shipDate = $pdo->query("SELECT DATE_FORMAT(MAX(source_file_date), '%Y-%m-%d') FROM stg_shipping_instruction")->fetchColumn() ?: null;

  // Excel part numbers: key -> [pn (as written), inv, ship]
  $excel = [];
  $load = function ($sql, $date, $flag) use ($pdo, &$excel) {
    if (!$date) return;
    $st = $pdo->prepare($sql);
    $st->execute([':d' => $date]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $pn) {
      $k = pm_key($pn);
      if ($k === '') continue;
      if (!isset($excel[$k])) $excel[$k] = ['pn' => trim((string)$pn), 'inv' => false, 'ship' => false];
      $excel[$k][$flag] = true;
    }
  };
  $load("SELECT DISTINCT production_number FROM stg_inventory_snapshot WHERE source_file_date = :d", $invDate, 'inv');
  $load("SELECT DISTINCT production_number FROM stg_shipping_instruction WHERE source_file_date = :d", $shipDate, 'ship');

  // master
  $byPn = [];
  $bySpn = [];
  $master = $pdo->query("SELECT id, production_number, stock_part_number FROM m_production_numbers")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($master as $m) {
    $byPn[pm_key($m['production_number'])] = $m;
    if ($m['stock_part_number'] !== null && $m['stock_part_number'] !== '') $bySpn[pm_key($m['stock_part_number'])] = $m;
  }

  $counts = [];
  foreach (['inv', 'ship'] as $f) $counts[$f] = ['total' => 0, 'exact' => 0, 'mapped' => 0, 'unmatched' => 0];
  $unmatched = [];
  foreach ($excel as $k => $e) {
    $kind = isset($byPn[$k]) ? 'exact' : (isset($bySpn[$k]) ? 'mapped' : 'unmatched');
    foreach (['inv', 'ship'] as $f) {
      if (!$e[$f]) continue;
      $counts[$f]['total']++;
      $counts[$f][$kind]++;
    }
    if ($kind !== 'unmatched') continue;

    // candidates: one is the beginning of the other, or the same text before the last hyphen
    $head = pm_head($k);
    $cands = [];
    foreach ($master as $m) {
      $mk = pm_key($m['production_number']);
      if (strlen($mk) < 4) continue;
      $prefix = str_starts_with($k, $mk) || str_starts_with($mk, $k);
      $sameHead = $head !== null && pm_head($mk) === $head;
      if (!$prefix && !$sameHead) continue;
      $cands[] = [
        'id' => (int)$m['id'],
        'production_number' => $m['production_number'],
        'stock_part_number' => $m['stock_part_number'],
        'reason' => $sameHead ? 'head' : 'prefix',
      ];
      if (count($cands) >= 8) break;
    }
    $unmatched[] = ['pn' => $e['pn'], 'inv' => $e['inv'], 'ship' => $e['ship'], 'candidates' => $cands];
  }
  usort($unmatched, fn($a, $b) => (count($b['candidates']) <=> count($a['candidates'])) ?: strcmp($a['pn'], $b['pn']));

  // the current map, and whether each Excel part number is in the latest files
  $map = [];
  foreach ($master as $m) {
    if ($m['stock_part_number'] === null || $m['stock_part_number'] === '') continue;
    $e = $excel[pm_key($m['stock_part_number'])] ?? null;
    $map[] = [
      'id' => (int)$m['id'],
      'production_number' => $m['production_number'],
      'stock_part_number' => $m['stock_part_number'],
      'inv' => $e ? $e['inv'] : false,
      'ship' => $e ? $e['ship'] : false,
    ];
  }
  usort($map, fn($a, $b) => strcmp($a['stock_part_number'], $b['stock_part_number']));

  echo json_encode([
    'status' => 'ok',
    'inventory_date' => $invDate,
    'shipping_date' => $shipDate,
    'counts' => $counts,
    'unmatched' => $unmatched,
    'map' => $map,
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  pm_fail($e->getMessage());
}
