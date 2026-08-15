<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$dieId = isset($_GET["die_id"]) ? (int)$_GET["die_id"] : 0;

$prepare = $pdo->prepare("
    SELECT
        id,
        DATE_FORMAT(plan_date_at, '%Y-%m-%d') AS plan_date_at,
        pressing_type_id,
        billet_size,
        billet_length,
        billet_input_quantity,
        ram_speed,
        stretch_ratio
    FROM t_press_directive
    WHERE dies_id = :die_id
    ORDER BY plan_date_at DESC, id DESC
    LIMIT 5
");
$prepare->bindValue(":die_id", $dieId, PDO::PARAM_INT);
$prepare->execute();

echo json_encode($prepare->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
