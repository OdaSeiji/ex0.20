<?php
// --------------------------------------------------
// DB 接続
// --------------------------------------------------
require_once "../db.php";

header("Content-Type: application/json; charset=utf-8");

// --------------------------------------------------
// 1. POST データ取得
// --------------------------------------------------
$press_id   = $_POST["press_id"];              // ★ 必須
$die_id     = $_POST["dies_id"];
$date       = $_POST["inspection_date"];
$staff_id   = $_POST["inspection_staff_id"];
$dim        = $_POST["dimension_result"];
$shape      = $_POST["shape_result"];
$overall    = $_POST["overall_result"];
$memo       = $_POST["memo"];

// --------------------------------------------------
// 2. INSERT（t_die_inspection）
// --------------------------------------------------
$sql = "INSERT INTO t_die_inspection 
        (press_id, die_id, inspection_date, inspection_staff_id,
         dimension_result, shape_result, overall_result, memo, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $press_id,
    $die_id,
    $date,
    $staff_id,
    $dim,
    $shape,
    $overall,
    $memo
]);

// 新しい inspection_id を取得
$inspection_id = $pdo->lastInsertId();

// --------------------------------------------------
// 3. 添付ファイル保存（uploads/inspection/{inspection_id}/）
// --------------------------------------------------
$upload_dir = "../../uploads/inspection/" . $inspection_id . "/";

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$files_saved = [];

if (!empty($_FILES["files"]["name"][0])) {

    for ($i = 0; $i < count($_FILES["files"]["name"]); $i++) {

        $tmp  = $_FILES["files"]["tmp_name"][$i];
        $name = basename($_FILES["files"]["name"][$i]);
        $type = $_FILES["files"]["type"][$i];

        // 保存先パス
        $save_path = $upload_dir . $name;

        // ファイル移動
        if (move_uploaded_file($tmp, $save_path)) {

            // DB 登録（t_die_attachment）
            $sql = "INSERT INTO t_die_attachment
                    (inspection_id, file_path, file_type, created_at)
                    VALUES (?, ?, ?, NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $inspection_id,
                $name,          // file_path はファイル名のみ
                $type
            ]);

            $files_saved[] = $name;
        }
    }
}

// --------------------------------------------------
// 4. レスポンス
// --------------------------------------------------
echo json_encode([
    "status" => "ok",
    "inspection_id" => $inspection_id,
    "files" => $files_saved
], JSON_UNESCAPED_UNICODE);
