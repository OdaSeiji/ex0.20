<?php
// Shared helpers for the stock import (stock_import.html).

// Normalize a part number for matching: trim spaces/tabs/newlines, upper case.
// (Production has part numbers with a leading tab, so we trim both sides.)
function pn_key($s) {
  return strtoupper(trim((string)$s, " \t\n\r\0\x0B\u{3000}"));
}

// Part numbers in m_production_numbers: [normalized key => part number as written in the master (trimmed)]
function known_pn_set(PDO $pdo) {
  $set = [];
  foreach ($pdo->query("SELECT production_number FROM m_production_numbers")->fetchAll(PDO::FETCH_COLUMN) as $pn) {
    $set[pn_key($pn)] = trim((string)$pn, " \t\n\r\0\x0B\u{3000}");
  }
  return $set;
}
