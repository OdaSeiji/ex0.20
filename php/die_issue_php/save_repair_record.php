<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// ---------------------------------------------
// デバッグログ出力
// ---------------------------------------------
file_put_contents("debug_log.txt", "\n===== NEW REQUEST =====\n", FILE_APPEND);
file_put_contents("debug_log.txt", "POST:\n" . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents("debug_log.txt", "FILES:\n" . print_r($_FILES, true) . "\n", FILE_APPEND);

// ---------------------------------------------
// DB Connection
// ---------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=extrusion;charset=utf8",
        "webuser",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents("debug_log.txt", "DB ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["error" => "DB Connection failed: " . $e->getMessage()]);
    exit;
}

// ---------------------------------------------
// Receive POST data
// ---------------------------------------------
$issue_id       = isset($_POST["issue_id"]) ? $_POST["issue_id"] : "";
$repair_date    = isset($_POST["repair_date"]) ? $_POST["repair_date"] : "";
$repair_comment = isset($_POST["repair_comment"]) ? $_POST["repair_comment"] : "";

// repaired_by → staff_id（固定）
$staff_id = 4;

// ---------------------------------------------
// Validate required fields
// ---------------------------------------------
if ($issue_id === "" || $repair_date === "" || $repair_comment === "") {
    file_put_contents("debug_log.txt", "ERROR: Missing required fields\n", FILE_APPEND);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// ---------------------------------------------
// INSERT into t_die_clinical_record
// ---------------------------------------------
$sql = "INSERT INTO t_die_clinical_record
        (issue_id, record_date, staff_id, memo)
        VALUES (:issue_id, :record_date, :staff_id, :memo)";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);
    $stmt->bindValue(":record_date", $repair_date, PDO::PARAM_STR);
    $stmt->bindValue(":staff_id", $staff_id, PDO::PARAM_INT);
    $stmt->bindValue(":memo", $repair_comment, PDO::PARAM_STR);
    $stmt->execute();
} catch (PDOException $e) {
    file_put_contents("debug_log.txt", "INSERT ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["error" => "INSERT error: " . $e->getMessage()]);
    exit;
}

$record_id = $pdo->lastInsertId();
file_put_contents("debug_log.txt", "lastInsertId: $record_id\n", FILE_APPEND);

// ---------------------------------------------
// Handle file uploads
// ---------------------------------------------
$upload_dir = "../../upload/02_repair_files/";

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!empty($_FILES["files"]["name"][0])) {

    for ($i = 0; $i < count($_FILES["files"]["name"]); $i++) {

        $orig_name = $_FILES["files"]["name"][$i];
        $tmp_name  = $_FILES["files"]["tmp_name"][$i];

        $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
        $save_name = "repair_" . $record_id . "_" . uniqid() . "." . $ext;

        $save_path = $upload_dir . $save_name;

        if (move_uploaded_file($tmp_name, $save_path)) {

            // 添付 INSERT（clinical_record_id / file_name / description）
            $sql2 = "INSERT INTO t_die_clinical_record_attachment
                     (clinical_record_id, file_name, description)
                     VALUES (:clinical_record_id, :file_name, :description)";

            try {
                $stmt2 = $pdo->prepare($sql2);
                $stmt2->bindValue(":clinical_record_id", $record_id, PDO::PARAM_INT);
                $stmt2->bindValue(":file_name", $save_name, PDO::PARAM_STR);
                $stmt2->bindValue(":description", "", PDO::PARAM_STR);
                $stmt2->execute();
            } catch (PDOException $e) {
                file_put_contents("debug_log.txt", "ATTACH INSERT ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
                echo json_encode(["error" => "Attachment insert error: " . $e->getMessage()]);
                exit;
            }

        } else {
            file_put_contents("debug_log.txt", "FILE MOVE ERROR: $orig_name\n", FILE_APPEND);
        }
    }
}

// ---------------------------------------------
// Return JSON
// ---------------------------------------------
echo json_encode([
    "status" => "success",
    "record_id" => $record_id
]);