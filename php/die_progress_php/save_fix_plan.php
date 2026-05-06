<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

/*
  POST で受け取るデータ
  - diagnosis_id
  - plan_fix_date
  - plan_fix_staff_id
  - plan_fix_content
  - fix_id（既存の場合のみ）
  - plan_files[]（添付ファイル）
*/

$diagnosis_id       = $_POST["diagnosis_id"];
$plan_fix_date      = $_POST["plan_fix_date"];
$plan_fix_staff_id  = $_POST["plan_fix_staff_id"];
$plan_fix_content   = $_POST["plan_fix_content"];
$fix_id             = $_POST["fix_id"] ?? null;

/* --------------------------------------------------
   1. 新規 or 更新
-------------------------------------------------- */
if ($fix_id) {
    // UPDATE
    $sql = "
        UPDATE t_die_fix
        SET
            plan_fix_date = ?,
            plan_fix_staff_id = ?,
            plan_fix_content = ?,
            plan_approval_status = 'pending',
            plan_approval_date = NULL,
            plan_approver_id = NULL,
            plan_reject_reason = NULL
        WHERE id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $plan_fix_date,
        $plan_fix_staff_id,
        $plan_fix_content,
        $fix_id
    ]);

    $mode = "update";

} else {
    // INSERT
    $sql = "
        INSERT INTO t_die_fix
        (
            diagnosis_id,
            plan_fix_date,
            plan_fix_staff_id,
            plan_fix_content,
            plan_approval_status,
            created_at
        )
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $diagnosis_id,
        $plan_fix_date,
        $plan_fix_staff_id,
        $plan_fix_content
    ]);

    $fix_id = $pdo->lastInsertId();
    $mode = "insert";
}

/* --------------------------------------------------
   2. 添付ファイル保存（plan_files[]）
-------------------------------------------------- */
if (!empty($_FILES["plan_files"]["name"][0])) {

    $dir = "../../uploads/fix/" . $fix_id;

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    foreach ($_FILES["plan_files"]["tmp_name"] as $i => $tmp) {
        $name = $_FILES["plan_files"]["name"][$i];
        $path = "$dir/$name";

        // ファイル保存
        move_uploaded_file($tmp, $path);

        // DB 登録
        $sql = "
            INSERT INTO t_die_attachment
            (fix_id, file_path, file_type, created_at)
            VALUES (?, ?, ?, NOW())
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $fix_id,
            $name,
            mime_content_type($path)
        ]);
    }
}

/* --------------------------------------------------
   3. 完了レスポンス
-------------------------------------------------- */
echo json_encode([
    "status" => "ok",
    "mode" => $mode,
    "fix_id" => $fix_id
]);
