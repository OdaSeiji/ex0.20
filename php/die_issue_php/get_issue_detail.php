<?php
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
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

// ---------------------------------------------
// Get issue_id from GET
// ---------------------------------------------
$issue_id = $_GET["id"] ?? "";

if ($issue_id === "") {
    echo json_encode(["error" => "Missing issue_id"]);
    exit;
}

// ---------------------------------------------
// Fetch Issue Basic Information
// ---------------------------------------------
$sql = "
SELECT 
    i.id,
    d.die_number,
    i.issue_title,
    i.issue_description,
    i.priority,
    i.approval_status,
    s1.staff_name AS assignee_name,
    s2.staff_name AS applicant_name,
    i.created_at
FROM t_die_issue i
LEFT JOIN m_dies d ON i.die_id = d.id
LEFT JOIN m_staff s1 ON i.assignee_id = s1.id
LEFT JOIN m_staff s2 ON i.applicant_id = s2.id
WHERE i.id = :issue_id
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);
$stmt->execute();

$issue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$issue) {
    echo json_encode(["error" => "Issue not found"]);
    exit;
}

// ---------------------------------------------
// Fetch Before Photos (Issue Attachments)
// ---------------------------------------------
$sql2 = "
SELECT 
    original_name,
    saved_name
FROM t_die_issue_attachment
WHERE issue_id = :issue_id
ORDER BY id ASC
";

$stmt2 = $pdo->prepare($sql2);
$stmt2->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);
$stmt2->execute();

$before_files = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------
// Build Response
// ---------------------------------------------
$response = [
    "id" => $issue["id"],
    "die_number" => $issue["die_number"],
    "issue_title" => $issue["issue_title"],
    "issue_description" => $issue["issue_description"],
    "priority" => $issue["priority"],
    "approval_status" => $issue["approval_status"],
    "assignee_name" => $issue["assignee_name"],
    "applicant_name" => $issue["applicant_name"],
    "created_at" => $issue["created_at"],
    "before_files" => $before_files
];

echo json_encode($response);