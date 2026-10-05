<?php
// 生産計画の一覧（右側）。期間・型番・号機で絞り込む。exd11 SelSummaryV9 の移植
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$start   = (string)($_GET["start"] ?? "");
$end     = (string)($_GET["end"] ?? "");
$die     = trim((string)($_GET["die"] ?? ""));
$machine = (int)($_GET["machine"] ?? 0);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT pl.id, pl.plan_date, pl.press_machine, pl.dies_id, d.die_number,
           pl.production_number_id, pn.production_number,
           pl.shift_id, pl.ordinal, pl.quantity, pl.billet_origin, pl.billet_length, pl.nitride_use, pl.note
    FROM t_press_plan pl
    LEFT JOIN m_dies d ON d.id = pl.dies_id
    LEFT JOIN m_production_numbers pn ON pn.id = pl.production_number_id
    WHERE pl.plan_date BETWEEN :start AND :end
      AND (:die1 = '' OR d.die_number LIKE :die2)
      AND (:m1 = 0 OR pl.press_machine = :m2)
    ORDER BY pl.plan_date DESC, pl.press_machine, pl.shift_id, pl.ordinal + 0, pl.id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([":start" => $start, ":end" => $end, ":die1" => $die, ":die2" => "%{$die}%", ":m1" => $machine, ":m2" => $machine]);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
