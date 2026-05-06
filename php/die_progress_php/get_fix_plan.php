<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

/*
  GET パラメータ
  - diagnosis_id
*/

$diagnosis_id = $_GET["diagnosis_id"];

/* --------------------------------------------------
   1. 修理計画（t_die_fix）を取得
-------------------------------------------------- */
$sql = "
    SELECT *
    FROM t_die_fix
    WHERE diagnosis_id = ?
    ORDER BY id DESC
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$diagnosis_id]);
$fix = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fix) {
    echo json_encode(null);
    exit;
}

$fix_id = $fix["id"];

/* --------------------------------------------------
   2. 添付ファイル（t_die_attachment）を取得
-------------------------------------------------- */
$sql = "
    SELECT id, fix_id, file_path, file_type, created_at
    FROM t_die_attachment
    WHERE fix_id = ?
    ORDER BY id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fix_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   3. JSON で返す
-------------------------------------------------- */
$fix["files"] = $files;

echo json_encode($fix);
