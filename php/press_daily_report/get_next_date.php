<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$row = $pdo->query("
    SELECT MAX(press_date_at) AS latest_date
    FROM t_press
")->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "latest_date" => $row["latest_date"] ?? date("Y-m-d"),
]);
