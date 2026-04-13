<?php
// ---------------------------------------------
// Debug Log（POST の受け取り確認）
// ---------------------------------------------
file_put_contents("debug_log.txt", "issue_id=" . ($_POST["issue_id"] ?? "NULL") . "\n", FILE_APPEND);

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// ---------------------------------------------
// DB Connection
// ---------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=extrusion;charset=utf8",
        "webuser",
        ""
    );
} catch (PDOException $e) {
    file_put_contents("debug_log.txt", "DB ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// ---------------------------------------------
// POST 受け取り
// ---------------------------------------------
$issue_id = $_POST["issue_id"] ?? "";

if ($issue_id === "") {
    file_put_contents("debug_log.txt", "ERROR: issue_id is empty\n", FILE_APPEND);
    echo json_encode(["error" => "Missing issue_id"]);
    exit;
}

// 承認者（ログイン機能ができるまで固定）
$approver_id = 4;

// ---------------------------------------------
// UPDATE t_die_issue
// ---------------------------------------------
$sql = "
UPDATE t_die_issue
SET 
    approval_status = 'approved',
    approver_id = :approver_id,
    approval_date = NOW()
WHERE id = :issue_id
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":approver_id", $approver_id, PDO::PARAM_INT);
$stmt->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);

// SQL 実行
if (!$stmt->execute()) {
    $error = $stmt->errorInfo();
    file_put_contents("debug_log.txt", "SQL ERROR: " . print_r($error, true) . "\n", FILE_APPEND);
    echo json_encode(["error" => "Failed to update issue"]);
    exit;
}

// rowCount をログに残す（WHERE がヒットしたか確認）
file_put_contents("debug_log.txt", "UPDATE OK, rowCount=" . $stmt->rowCount() . "\n", FILE_APPEND);

// ---------------------------------------------
// Return JSON
// ---------------------------------------------
echo json_encode([
    "status" => "success",
    "issue_id" => $issue_id
]);