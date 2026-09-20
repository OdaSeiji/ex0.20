<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$body = json_decode(file_get_contents("php://input"), true);
$id = (int)($body["id"] ?? 0);
if ($id === 0) {
    echo json_encode(["status" => "error", "message" => "id is required"]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM t_press_directive WHERE id = :id");
    $stmt->execute([":id" => $id]);
    echo json_encode(["status" => "ok"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
