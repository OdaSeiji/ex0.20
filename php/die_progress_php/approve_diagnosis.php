<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

/* ----------------------------------------
   1. press_id を受け取る
---------------------------------------- */
$press_id = $_GET["press_id"] ?? null;

if (!$press_id) {
    echo json_encode(["status" => "error", "message" => "press_id がありません"]);
    exit;
}

/* ----------------------------------------
   2. press_id → inspection を取得（die_id も取得）
---------------------------------------- */
$sql = "SELECT id, die_id FROM t_die_inspection WHERE press_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$press_id]);
$inspection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inspection) {
    echo json_encode(["status" => "error", "message" => "inspection が見つかりません"]);
    exit;
}

$inspection_id = $inspection["id"];
$die_id        = $inspection["die_id"];

/* ----------------------------------------
   3. 最新の diagnosis を取得
---------------------------------------- */
$sql = "SELECT * FROM t_die_diagnosis 
        WHERE inspection_id = ? 
        ORDER BY id DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$inspection_id]);
$diagnosis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$diagnosis) {
    echo json_encode(["status" => "error", "message" => "diagnosis が見つかりません"]);
    exit;
}

$diagnosis_id      = $diagnosis["id"];
$overall_judgement = $diagnosis["overall_judgement"];
$die_issue_id      = $diagnosis["die_issue_id"];

/* ----------------------------------------
   4. 承認処理
---------------------------------------- */
$sql = "
    UPDATE t_die_diagnosis
    SET 
        approval_status = 'approved',
        approver_id = '9999',
        approval_date = NOW()
    WHERE id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$diagnosis_id]);

/* ----------------------------------------
   5. NG の場合 → Issue 自動登録
---------------------------------------- */
if ($overall_judgement === "NG" && empty($die_issue_id)) {

    /* ----------------------------------------
       5-1. 同じ die_id の open Issue があるか確認
    ---------------------------------------- */
    $sql = "SELECT id FROM t_die_issue 
            WHERE die_id = ? AND status = 'open' 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$die_id]);
    $existing_issue = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_issue) {
        // 既に open Issue がある → 新規作成しない
        // diagnosis に既存 issue_id を紐づける
        $sql = "UPDATE t_die_diagnosis SET die_issue_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$existing_issue["id"], $diagnosis_id]);
    } else {

        /* ----------------------------------------
           5-2. Issue 新規作成
        ---------------------------------------- */
        $sql = "INSERT INTO t_die_issue 
                (die_id, issue_title, issue_detail, priority, status)
                VALUES (?, ?, ?, 3, 'open')";
        $stmt = $pdo->prepare($sql);

        $title  = "診断NG（診断ID: {$diagnosis_id}）";
        $detail = "診断でNG判定が出たため自動登録";

        $stmt->execute([$die_id, $title, $detail]);

        $new_issue_id = $pdo->lastInsertId();

        // diagnosis に issue_id を紐づける
        $sql = "UPDATE t_die_diagnosis SET die_issue_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_issue_id, $diagnosis_id]);
    }
}

/* ----------------------------------------
   6. 完了レスポンス
---------------------------------------- */
echo json_encode([
    "status" => "success",
    "message" => "承認処理が完了しました",
    "redirect" => "../../die_progress_list.html"
]);
exit;

?>
