<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$input  = json_decode(file_get_contents("php://input"), true);
$action = $input["action"] ?? null;
$id     = $input["id"]     ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID required"]);
    exit;
}

if ($action === "update_role") {
    $role    = $input["role"] ?? null;
    $allowed = ["admin", "operator", "maintenance", "inspector", "die_setup", "die_engineer"];

    if ($role !== null && !in_array($role, $allowed)) {
        echo json_encode(["status" => "error", "message" => "invalid role"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE m_staff SET role = ? WHERE id = ?");
    $stmt->execute([$role, $id]);
    echo json_encode(["status" => "ok"]);

} elseif ($action === "reset_password") {
    $emploee_number = $input["emploee_number"] ?? "0000";
    $hashed = password_hash((string)$emploee_number, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE m_staff SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $id]);
    echo json_encode(["status" => "ok"]);

} else {
    echo json_encode(["status" => "error", "message" => "unknown action"]);
}
