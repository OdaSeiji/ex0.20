<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

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
