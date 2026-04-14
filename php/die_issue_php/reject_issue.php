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
$approved_by = 4;

if ($issue_id === "") {
    echo json_encode(["error" => "Missing issue_id"]);
    exit;
}

$sql = "
UPDATE t_die_issue
SET 
    approval_status = 'rejected',
    approved_by = :approved_by,
    approved_at = NOW(),
    completion_status = 'not_applicable'  -- 却下されたら完了フロー対象外
WHERE id = :issue_id
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":approved_by", $approved_by, PDO::PARAM_INT);
$stmt->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);

if (!$stmt->execute()) {
    echo json_encode(["error" => $stmt->errorInfo()]);
    exit;
}

echo json_encode(["status" => "success"]);