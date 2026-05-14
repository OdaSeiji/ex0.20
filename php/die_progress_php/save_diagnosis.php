<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

/*
  受け取る POST データ
  - press_id
  - diagnosis_date
  - diagnosis_staff
  - dimension_judgement
  - shape_judgement
  - overall_judgement
  - ng_action
  - condition_change
  - memo
  - diag_files[]   ← 添付ファイル
*/

// --------------------------------------------------
// 1. POST データ取得
// --------------------------------------------------
$press_id           = $_POST["press_id"];
$diagnosis_date     = $_POST["diagnosis_date"];
$diagnosis_staff    = $_POST["diagnosis_staff"];
$dim_judge          = $_POST["dimension_judgement"];
$shape_judge        = $_POST["shape_judgement"];
$overall_judge      = $_POST["overall_judgement"];
$ng_action          = $_POST["ng_action"];
$condition_change   = $_POST["condition_change"] ?? null;
$memo               = $_POST["memo"] ?? null;

// --------------------------------------------------
// 2. press_id → inspection_id を取得
// --------------------------------------------------
$sql = "SELECT id FROM t_die_inspection WHERE press_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$press_id]);
$inspection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inspection) {
    echo json_encode(["status" => "error", "message" => "inspection not found"]);
    exit;
}

$inspection_id = $inspection["id"];

// --------------------------------------------------
// 3. 既存診断があるか確認
// --------------------------------------------------
$sql = "SELECT id FROM t_die_diagnosis WHERE inspection_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$inspection_id]);
$exist = $stmt->fetch(PDO::FETCH_ASSOC);

// --------------------------------------------------
// 4. INSERT or UPDATE
// --------------------------------------------------
if ($exist) {
    // UPDATE（★ 再診断 → 承認ステータスを未承認に戻す）
    $sql = "
        UPDATE t_die_diagnosis
        SET
            diagnosis_date = ?,
            diagnosis_staff = ?,
            dimension_judgement = ?,
            shape_judgement = ?,
            overall_judgement = ?,
            ng_action = ?,
            condition_change = ?,
            memo = ?,
            approval_status = 'pending',   -- ★ 未承認に戻す
            approver_id = NULL,            -- ★ 承認者クリア
            approval_date = NULL           -- ★ 承認日時クリア
        WHERE inspection_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $diagnosis_date,
        $diagnosis_staff,
        $dim_judge,
        $shape_judge,
        $overall_judge,
        $ng_action,
        $condition_change,
        $memo,
        $inspection_id
    ]);

    $diagnosis_id = $exist["id"];
    $mode = "update";

} else {
    // INSERT
    $sql = "
        INSERT INTO t_die_diagnosis
        (
            inspection_id,
            diagnosis_date,
            diagnosis_staff,
            dimension_judgement,
            shape_judgement,
            overall_judgement,
            ng_action,
            condition_change,
            memo,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $inspection_id,
        $diagnosis_date,
        $diagnosis_staff,
        $dim_judge,
        $shape_judge,
        $overall_judge,
        $ng_action,
        $condition_change,
        $memo
    ]);

    $diagnosis_id = $pdo->lastInsertId();
    $mode = "insert";
}

// --------------------------------------------------
// 5. 添付ファイル保存（diag_files[]）
// --------------------------------------------------
if (!empty($_FILES["diag_files"]["name"][0])) {

    $dir = "../../uploads/diagnosis/" . $diagnosis_id;

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    foreach ($_FILES["diag_files"]["tmp_name"] as $i => $tmp) {
        $name = $_FILES["diag_files"]["name"][$i];
        $path = "$dir/$name";

        // ファイル保存
        move_uploaded_file($tmp, $path);

        // DB 登録（t_die_attachment に diagnosis_id で紐づける）
        $sql = "INSERT INTO t_die_attachment 
                (diagnosis_id, file_path, file_type, created_at)
                VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $diagnosis_id,
            $name,
            mime_content_type($path)
        ]);
    }
}

// --------------------------------------------------
// 6. 完了レスポンス
// --------------------------------------------------
echo json_encode([
    "status" => "ok",
    "mode" => $mode,
    "diagnosis_id" => $diagnosis_id
]);
