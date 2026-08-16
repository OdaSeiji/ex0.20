<?php
header("Content-Type: application/json; charset=UTF-8");

$ALLOWED_EXT = ["jpg", "jpeg", "png", "gif", "pdf"];
$MAX_SIZE    = 15 * 1024 * 1024; // 15MB

if (!isset($_FILES["scan_file"]) || $_FILES["scan_file"]["error"] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(["error" => "ファイルが選択されていません"], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES["scan_file"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["error" => "アップロードに失敗しました (code: {$file['error']})"], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($file["size"] > $MAX_SIZE) {
    http_response_code(400);
    echo json_encode(["error" => "ファイルサイズが大きすぎます（15MBまで）"], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_uploaded_file($file["tmp_name"])) {
    http_response_code(400);
    echo json_encode(["error" => "不正なアップロードです"], JSON_UNESCAPED_UNICODE);
    exit;
}

$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
if (!in_array($ext, $ALLOWED_EXT, true)) {
    http_response_code(400);
    echo json_encode(["error" => "許可されていないファイル形式です（jpg/jpeg/png/gif/pdfのみ）"], JSON_UNESCAPED_UNICODE);
    exit;
}

$targetDir = realpath(__DIR__ . "/../../../diereport/upload/01_press_directive");
if ($targetDir === false) {
    http_response_code(500);
    echo json_encode(["error" => "保存先ディレクトリが見つかりません"], JSON_UNESCAPED_UNICODE);
    exit;
}

$filename = date("Ymd-His") . "-" . bin2hex(random_bytes(3)) . "." . $ext;
$destPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file["tmp_name"], $destPath)) {
    http_response_code(500);
    echo json_encode(["error" => "ファイルの保存に失敗しました"], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(["filename" => $filename], JSON_UNESCAPED_UNICODE);
