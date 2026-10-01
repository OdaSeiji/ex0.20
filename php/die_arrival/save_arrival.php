<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

// 2026-10-01 停止中：到着日は引き継ぎ一覧（t_die_handover.die_arrived_at）を正とするため、この画面からは保存しない。
// （この画面は m_dies・t_die_handover・t_die_handover_progress の3か所の到着日を同時に書き換える作りだった）
// 再開するときは、この停止を外し、die_arrival.html の PAGE_DISABLED を false にする。
http_response_code(410);
echo json_encode(["status" => "error", "message" => "この画面は停止中です。到着日は引き継ぎ一覧で入力してください。"], JSON_UNESCAPED_UNICODE);
exit;

$rows = json_decode(file_get_contents("php://input"), true);

if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$stmtDie  = $pdo->prepare("UPDATE m_dies SET arrival_at = ? WHERE id = ?");
$stmtHO   = $pdo->prepare("UPDATE t_die_handover SET die_arrived_at = ? WHERE die_id = ?");
$stmtProg = $pdo->prepare("UPDATE t_die_handover_progress SET arrival_at = ? WHERE die_id = ?");

$updated = 0;
foreach ($rows as $row) {
    $id   = intval($row["id"]);
    $date = $row["arrival_at"] ?: null;
    if (!$id || !$date) continue;

    $stmtDie->execute([$date, $id]);
    $stmtHO->execute([$date, $id]);
    $stmtProg->execute([$date, $id]);
    $updated++;
}

echo json_encode(["status" => "ok", "updated" => $updated]);
