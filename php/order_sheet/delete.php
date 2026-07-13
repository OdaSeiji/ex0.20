<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data["id"] ?? 0);
$password = trim($data["password"] ?? "");

if (!$id || $password === "") {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

if ($password !== "1031") {
    echo json_encode(["status" => "error", "message" => "permission denied"]);
    exit;
}

$pressStmt = $pdo->prepare("SELECT COUNT(*) FROM t_press WHERE ordersheet_id = ?");
$pressStmt->execute([$id]);

if ($pressStmt->fetchColumn() > 0) {
    echo json_encode(["status" => "error", "message" => "in use by press records"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM m_ordersheet WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["status" => "ok", "deleted" => $stmt->rowCount()]);
