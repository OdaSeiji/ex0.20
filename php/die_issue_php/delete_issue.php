<?php
header("Content-Type: application/json");

$pdo = new PDO("mysql:host=localhost;dbname=extrusion;charset=utf8", "webuser", "");

$id = $_GET["id"] ?? "";

$sql = "DELETE FROM t_die_issue WHERE id = :id AND approval_status = 'pending'";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $id, PDO::PARAM_INT);
$stmt->execute();

echo json_encode(["status" => "success"]);