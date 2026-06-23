<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->prepare("
        UPDATE m_dies SET
            die_number          = :die_number,
            production_number_id= :production_number_id,
            die_diamater_id     = :die_diamater_id,
            bolstar_id          = :bolstar_id,
            hole                = :hole,
            arrival_at          = :arrival_at,
            updated_at          = :updated_at,
            die_postition       = :die_postition
        WHERE id = :targetId
    ");
    $arrivalDate = !empty($_POST['arrival_date']) ? $_POST['arrival_date'] : null;
    $stmt->execute([
        ':die_number'          => $_POST['die_number'],
        ':die_postition'       => $_POST['die_postition'],
        ':production_number_id'=> (int)$_POST['production_number_id'],
        ':die_diamater_id'     => (int)$_POST['die_diamater__select'],
        ':bolstar_id'          => (int)$_POST['bolster__select'],
        ':hole'                => (int)$_POST['whole__input'],
        ':arrival_at'          => $arrivalDate,
        ':updated_at'          => $_POST['today'],
        ':targetId'            => (int)$_POST['targetId'],
    ]);
    echo json_encode("UPDATED");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
