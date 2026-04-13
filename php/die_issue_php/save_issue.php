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
    echo json_encode(["error" => "DB Connection failed"]);
    exit;
}

// ---------------------------------------------
// Receive POST data
// ---------------------------------------------
$die_id            = $_POST["die_id"] ?? "";
$issue_title       = $_POST["issue_title"] ?? "";
$issue_description = $_POST["issue_description"] ?? "";
$assignee_id       = $_POST["assignee_id"] ?? "";
$priority          = $_POST["priority"] ?? "middle";

// applicant_id は固定（ログイン機能ができるまで）
$applicant_id = 4;

// ---------------------------------------------
// Validate required fields
// ---------------------------------------------
if ($die_id === "" || $issue_title === "" || $issue_description === "" || $assignee_id === "") {
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// ---------------------------------------------
// INSERT into t_die_issue
// ---------------------------------------------
$sql = "INSERT INTO t_die_issue 
        (die_id, issue_title, issue_description, assignee_id, applicant_id, priority)
        VALUES (:die_id, :title, :description, :assignee_id, :applicant_id, :priority)";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":die_id", $die_id, PDO::PARAM_INT);
$stmt->bindValue(":title", $issue_title, PDO::PARAM_STR);
$stmt->bindValue(":description", $issue_description, PDO::PARAM_STR);
$stmt->bindValue(":assignee_id", $assignee_id, PDO::PARAM_INT);
$stmt->bindValue(":applicant_id", $applicant_id, PDO::PARAM_INT);
$stmt->bindValue(":priority", $priority, PDO::PARAM_STR);

$stmt->execute();

// Newly created issue ID
$issue_id = $pdo->lastInsertId();

// ---------------------------------------------
// Handle file uploads
// ---------------------------------------------
$upload_dir = "../../upload/01_die_issue_files/";

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!empty($_FILES["files"]["name"][0])) {

    for ($i = 0; $i < count($_FILES["files"]["name"]); $i++) {

        $orig_name = $_FILES["files"]["name"][$i];
        $tmp_name  = $_FILES["files"]["tmp_name"][$i];

        // Unique file name
        $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
        $save_name = "issue_" . $issue_id . "_" . uniqid() . "." . $ext;

        $save_path = $upload_dir . $save_name;

        if (move_uploaded_file($tmp_name, $save_path)) {

            // Insert into t_die_issue_attachment
            $sql2 = "INSERT INTO t_die_issue_attachment 
                     (issue_id, original_name, saved_name)
                     VALUES (:issue_id, :orig, :save)";

            $stmt2 = $pdo->prepare($sql2);
            $stmt2->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);
            $stmt2->bindValue(":orig", $orig_name, PDO::PARAM_STR);
            $stmt2->bindValue(":save", $save_name, PDO::PARAM_STR);
            $stmt2->execute();
        }
    }
}

// ---------------------------------------------
// Return JSON
// ---------------------------------------------
echo json_encode([
    "status" => "success",
    "issue_id" => $issue_id
]);