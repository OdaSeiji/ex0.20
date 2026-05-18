<?php
// ===============================================
// 修理実施報告 保存処理（修正版）
// ===============================================

require_once "../db.php";
header("Content-Type: text/plain; charset=UTF-8");

// -----------------------------------------------
// 入力値
// -----------------------------------------------
$press_id = $_POST["press_id"] ?? null;
$actual_fix_date = $_POST["actual_fix_date"] ?? null;
$actual_fix_staff_id = $_POST["actual_fix_staff_id"] ?? null;
$actual_fix_content = $_POST["actual_fix_content"] ?? null;

if (!$press_id) {
    echo "press_id がありません";
    exit;
}

/* --------------------------------------------------
   press_id → inspection → diagnosis → fix を取得
-------------------------------------------------- */
$sql = "
    SELECT 
        f.id AS fix_id,
        d.id AS diagnosis_id
    FROM t_die_inspection i
    JOIN t_die_diagnosis d ON i.id = d.inspection_id
    JOIN t_die_fix f ON d.id = f.diagnosis_id
    WHERE i.press_id = ?
    ORDER BY d.id DESC
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$press_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "修理計画が見つかりません";
    exit;
}

$fix_id = $row["fix_id"];
$diagnosis_id = $row["diagnosis_id"];

/* --------------------------------------------------
   t_die_fix を UPDATE（実施報告）
   ※ fix_result を削除
-------------------------------------------------- */
$sql = "
    UPDATE t_die_fix
    SET 
        actual_fix_date = ?,
        actual_fix_staff_id = ?,
        actual_fix_content = ?
    WHERE id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $actual_fix_date,
    $actual_fix_staff_id,
    $actual_fix_content,
    $fix_id
]);

/* --------------------------------------------------
   添付ファイル保存（uploads/fix/{fix_id}/）
-------------------------------------------------- */
$upload_dir = "../../uploads/fix/" . $fix_id . "/";

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!empty($_FILES["files"]["name"][0])) {

    foreach ($_FILES["files"]["name"] as $i => $name) {

        $tmp = $_FILES["files"]["tmp_name"][$i];
        $type = $_FILES["files"]["type"][$i];

        // 保存ファイル名（重複防止）
        $save_name = date("Ymd_His") . "_" . basename($name);
        $path = $upload_dir . $save_name;

        if (move_uploaded_file($tmp, $path)) {

            // DB に登録
            $sql = "
                INSERT INTO t_die_attachment
                (diagnosis_id, fix_id, file_path, file_type, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $diagnosis_id,
                $fix_id,
                $save_name,
                $type
            ]);
        }
    }
}

/* --------------------------------------------------
   完了
-------------------------------------------------- */
echo "修理実施報告を保存しました。";

?>
