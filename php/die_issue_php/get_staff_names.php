<?php
$pdo = new PDO("mysql:host=localhost;dbname=extrusion;charset=utf8", "webuser", "");

$sql = "SELECT staff_name FROM m_staff ORDER BY staff_name";
$stmt = $pdo->query($sql);

$result = $stmt->fetchAll(PDO::FETCH_COLUMN);

header("Content-Type: application/json");
echo json_encode($result);