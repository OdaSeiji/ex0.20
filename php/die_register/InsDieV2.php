<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->prepare("
    INSERT INTO m_dies (die_number, production_number_id, die_diamater_id,
        bolstar_id, hole, arrival_at, created_at, die_postition)
    VALUES (:die_number, :production_number_id, :die_diamater_id,
        :bolstar_id, :hole, :arrival_at, :created_at, :die_postition)
");
$stmt->execute([
    ':die_number'          => $_POST['die_number'],
    ':die_postition'       => $_POST['die_postition'],
    ':production_number_id'=> (int)$_POST['production_number_id'],
    ':die_diamater_id'     => (int)$_POST['die_diamater__select'],
    ':bolstar_id'          => (int)$_POST['bolster__select'],
    ':hole'                => (int)$_POST['whole__input'],
    ':arrival_at'          => $_POST['arrival_date'],
    ':created_at'          => $_POST['today'],
]);
echo json_encode("INSERTED");
