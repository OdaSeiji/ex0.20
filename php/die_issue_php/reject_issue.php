<?php
header("Content-Type: application/json");

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=extrusion;charset=utf8",
        "webuser",
        ""
    );
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

$issue_id = $_POST["issue_id"] ?? "";
$approver_id = 4;

$sql = "
UPDATE t_die_issue
SET 
    approval_status = 'rejected',
    approver_id = :approver_id,
    approval_date = NOW()
WHERE id = :issue_id
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":approver_id", $approver_id, PDO::PARAM_INT);
$stmt->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);

if (!$stmt->execute()) {
    $error = $stmt->errorInfo();
    echo json_encode(["error" => $error]);
    exit;
}

echo json_encode(["status" => "success"]);