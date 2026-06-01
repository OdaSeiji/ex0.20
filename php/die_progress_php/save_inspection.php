<?php
require_once "../db.php";
header("Content-Type: application/json; charset=utf-8");

// --------------------------------------------------
// 1. POST データ取得
// --------------------------------------------------
$press_id   = $_POST["press_id"];              // ★ 主キー扱い
$die_id     = $_POST["dies_id"];
$date       = $_POST["inspection_date"];
$staff_id   = $_POST["inspection_staff_id"];
$dim        = $_POST["dimension_result"];
$shape      = $_POST["shape_result"];
$overall    = $_POST["overall_result"];
$memo       = $_POST["memo"];
$cmm        = $_POST["cmm"]   ?? null;
$lm_im      = $_POST["lm_im"] ?? null;
$gage       = $_POST["gage"]  ?? null;

// --------------------------------------------------
// 2. press_id のレコードが存在するか確認
// --------------------------------------------------
$sql = "SELECT id FROM t_die_inspection WHERE press_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$press_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

// --------------------------------------------------
// 3. INSERT or UPDATE
// --------------------------------------------------
if ($existing) {
    // ★ UPDATE
    $inspection_id = $existing["id"];

    $sql = "
        UPDATE t_die_inspection
        SET
            inspection_date = ?,
            inspection_staff_id = ?,
            dimension_result = ?,
            shape_result = ?,
            overall_result = ?,
            memo = ?,
            cmm = ?,
            lm_im = ?,
            gage = ?
        WHERE press_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $date,
        $staff_id,
        $dim,
        $shape,
        $overall,
        $memo,
        $cmm,
        $lm_im,
        $gage,
        $press_id
    ]);

} else {
    // ★ INSERT
    $sql = "
        INSERT INTO t_die_inspection
        (press_id, die_id, inspection_date, inspection_staff_id,
         dimension_result, shape_result, overall_result, memo, cmm, lm_im, gage, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $press_id,
        $die_id,
        $date,
        $staff_id,
        $dim,
        $shape,
        $overall,
        $memo,
        $cmm,
        $lm_im,
        $gage
    ]);

    $inspection_id = $pdo->lastInsertId();
}

// --------------------------------------------------
// 4. 添付ファイル保存（UPDATE の場合は古いファイル削除）
// --------------------------------------------------
$upload_dir = "../../uploads/inspection/" . $inspection_id . "/";

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$files_saved = [];

// ★ UPDATE の場合は古いファイル削除
if ($existing) {
    // 物理ファイル削除
    $old_files = glob($upload_dir . "*");
    foreach ($old_files as $f) {
        unlink($f);
    }

    // DB の添付ファイル削除
    $pdo->prepare("DELETE FROM t_die_attachment WHERE inspection_id = ?")
        ->execute([$inspection_id]);
}

// ★ 新しいファイル保存
if (!empty($_FILES["files"]["name"][0])) {

    for ($i = 0; $i < count($_FILES["files"]["name"]); $i++) {

        $tmp  = $_FILES["files"]["tmp_name"][$i];
        $name = basename($_FILES["files"]["name"][$i]);
        $type = $_FILES["files"]["type"][$i];

        $save_path = $upload_dir . $name;

        if (move_uploaded_file($tmp, $save_path)) {

            $sql = "
                INSERT INTO t_die_attachment
                (inspection_id, file_path, file_type, created_at)
                VALUES (?, ?, ?, NOW())
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $inspection_id,
                $name,
                $type
            ]);

            $files_saved[] = $name;
        }
    }
}

// --------------------------------------------------
// 5. レスポンス
// --------------------------------------------------
echo json_encode([
    "status" => "ok",
    "inspection_id" => $inspection_id,
    "files" => $files_saved
], JSON_UNESCAPED_UNICODE);
