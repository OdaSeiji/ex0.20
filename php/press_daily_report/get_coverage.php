<?php
// 押出日報の入力確認（サンプル版）：号機と期間を選び、日ごとの押出の記録（開始・終了）を返す
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$machine = (int)($_GET["machine"] ?? 0);
$start   = (string)($_GET["start"] ?? "");
$days    = max(1, min(31, (int)($_GET["days"] ?? 7)));
if ($machine < 1 || $machine > 4 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
    echo json_encode([]);
    exit;
}
$end = date("Y-m-d", strtotime("{$start} +" . ($days - 1) . " day"));

$stmt = $pdo->prepare("
    SELECT p.id, p.press_date_at, d.die_number,
           TIME_FORMAT(p.press_start_at, '%H:%i') AS start_at,
           TIME_FORMAT(p.press_finish_at, '%H:%i') AS finish_at,
           p.actual_billet_quantities AS billets, pt.pressing_type, p.entry_source
    FROM t_press p
    LEFT JOIN m_dies d ON d.id = p.dies_id
    LEFT JOIN m_pressing_type pt ON pt.id = p.pressing_type_id
    WHERE p.press_machine_no = :mc AND p.press_date_at BETWEEN :s AND :e
    ORDER BY p.press_date_at, p.press_start_at, p.id
");
$stmt->execute([":mc" => $machine, ":s" => $start, ":e" => $end]);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
