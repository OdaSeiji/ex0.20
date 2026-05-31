<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

session_start();
require_once __DIR__ . "/../db.php";

$input       = json_decode(file_get_contents("php://input"), true);
$emp_number  = trim($input["emploee_number"] ?? "");
$password    = $input["password"] ?? "";

ob_end_clean();
header("Content-Type: application/json; charset=UTF-8");

if ($emp_number === "" || $password === "") {
    echo json_encode(["status" => "error", "message" => "社員番号とパスワードを入力してください"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, staff_name, role, password
    FROM m_staff
    WHERE emploee_number = ?
      AND leave_at IS NULL
    LIMIT 1
");
$stmt->execute([$emp_number]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff || !$staff["password"] || !password_verify($password, $staff["password"])) {
    echo json_encode(["status" => "error", "message" => "社員番号またはパスワードが正しくありません"]);
    exit;
}

$_SESSION["staff_id"]   = $staff["id"];
$_SESSION["staff_name"] = $staff["staff_name"];
$_SESSION["role"]       = $staff["role"];

echo json_encode(["status" => "ok", "name" => $staff["staff_name"], "role" => $staff["role"]]);
