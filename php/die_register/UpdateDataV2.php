<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->prepare("
    UPDATE m_dies SET
        die_number          = :die_number,
        production_number_id= :production_number_id,
        die_diamater_id     = :die_diamater_id,
        bolstar_id          = :bolstar_id,
        hole                = :hole,
        arrival_at          = :arrival_at,
        die_diameter        = :die_diameter,
        updated_at          = :updated_at,
        die_postition       = :die_postition
    WHERE id = :targetId
");
$stmt->execute([
    ':die_number'          => $_POST['die_number'],
    ':die_postition'       => $_POST['die_postition'],
    ':production_number_id'=> (int)$_POST['production_number_id'],
    ':die_diamater_id'     => (int)$_POST['die_diamater__select'],
    ':bolstar_id'          => (int)$_POST['bolster__select'],
    ':hole'                => (int)$_POST['whole__input'],
    ':arrival_at'          => $_POST['arrival_date'],
    ':die_diameter'        => (int)$_POST['die_diamater__select'],
    ':updated_at'          => $_POST['today'],
    ':targetId'            => (int)$_POST['targetId'],
]);
echo json_encode("UPDATED");
