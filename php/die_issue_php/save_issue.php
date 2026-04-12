<?php
// ---------------------------------------------
// Database connection
// ---------------------------------------------
$pdo = new PDO(
    "mysql:host=localhost;dbname=extrusion;charset=utf8",
    "webuser",
    ""
);

// ---------------------------------------------
// Receive POST data
// ---------------------------------------------
$die_id          = $_POST["die_id"] ?? null;
$issue_title     = $_POST["issue_title"] ?? "";
$issue_desc      = $_POST["issue_description"] ?? "";
$assignee_id     = $_POST["assignee_id"] ?? null;

// Applicant is fixed value = 4
$applicant_id = 4;

// ---------------------------------------------
// Basic validation
// ---------------------------------------------
if (!$die_id || !$assignee_id || $issue_title === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields."
    ]);
    exit;
}

// ---------------------------------------------
// Insert into t_die_issue
// ---------------------------------------------
$sql = "INSERT INTO t_die_issue 
        (die_id, issue_title, issue_description, assignee_id, applicant_id)
        VALUES (:die_id, :title, :description, :assignee_id, :applicant_id)";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":die_id", $die_id, PDO::PARAM_INT);
$stmt->bindValue(":title", $issue_title, PDO::PARAM_STR);
$stmt->bindValue(":description", $issue_desc, PDO::PARAM_STR);
$stmt->bindValue(":assignee_id", $assignee_id, PDO::PARAM_INT);
$stmt->bindValue(":applicant_id", $applicant_id, PDO::PARAM_INT);
$stmt->execute();

$issue_id = $pdo->lastInsertId();

// ---------------------------------------------
// File upload handling
// ---------------------------------------------
$uploadDir = "../../upload/01_die_issue_files/";

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (isset($_FILES["files"]["tmp_name"]) && is_array($_FILES["files"]["tmp_name"])) {

    foreach ($_FILES["files"]["tmp_name"] as $index => $tmpPath) {

        if (!is_uploaded_file($tmpPath)) {
            continue;
        }

        $originalName = $_FILES["files"]["name"][$index];

        $newFileName = $issue_id . "_" . uniqid() . "_" . $originalName;
        $savePath = $uploadDir . $newFileName;

        move_uploaded_file($tmpPath, $savePath);

        $sql = "INSERT INTO t_die_issue_attachment 
                (issue_id, file_name, file_path)
                VALUES (:issue_id, :file_name, :file_path)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);
        $stmt->bindValue(":file_name", $originalName, PDO::PARAM_STR);
        $stmt->bindValue(":file_path", $newFileName, PDO::PARAM_STR);
        $stmt->execute();
    }
}

// ---------------------------------------------
// Return JSON response
// ---------------------------------------------
echo json_encode([
    "status" => "success",
    "issue_id" => $issue_id
]);
