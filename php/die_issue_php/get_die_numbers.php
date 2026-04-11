<?php
$pdo = new PDO("mysql:host=localhost;dbname=extrusion;charset=utf8", "webuser", "");

$sql = "SELECT die_number FROM m_dies ORDER BY die_number";
$stmt = $pdo->query($sql);

$result = $stmt->fetchAll(PDO::FETCH_COLUMN);

header("Content-Type: application/json");
echo json_encode($result);