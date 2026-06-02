<?php
ini_set('display_errors', '0');
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$input = json_decode(file_get_contents("php://input"), true);
$id    = $input["id"] ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID required"]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM t_dies_import_tmp WHERE id = ? AND import_flag = 0");
    $stmt->execute([$id]);
    echo json_encode(["status" => "ok"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
