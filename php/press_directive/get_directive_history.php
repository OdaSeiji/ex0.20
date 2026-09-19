<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$diesId = (int)($_GET["dies_id"] ?? 0);
if ($diesId === 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.*
    FROM t_press_directive t
    WHERE t.dies_id = :dies_id
    ORDER BY t.plan_date_at DESC, t.id DESC
    LIMIT 10
");
$stmt->execute([":dies_id" => $diesId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
