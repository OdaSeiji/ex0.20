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
   2. press_id → inspection_id を取得
---------------------------------------- */
$sql = "SELECT id FROM t_die_inspection WHERE press_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$press_id]);
$inspection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inspection) {
    echo json_encode(["status" => "error", "message" => "inspection が見つかりません"]);
    exit;
}

$inspection_id = $inspection["id"];

/* ----------------------------------------
   3. inspection_id → diagnosis を取得
---------------------------------------- */
$sql = "SELECT id, ng_action FROM t_die_diagnosis WHERE inspection_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$inspection_id]);
$diagnosis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$diagnosis) {
    echo json_encode(["status" => "error", "message" => "diagnosis が見つかりません"]);
    exit;
}

$diagnosis_id = $diagnosis["id"];
$ng_action = $diagnosis["ng_action"];

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
   5. 遷移先を決定
---------------------------------------- */
if ($ng_action == 1) {
    // (1) 様子を見る → 修理へ
    $redirect = "../../die_fix_plan.html?press_id=" . $press_id;

} elseif ($ng_action == 2) {
    // (2) 修理 → 次回押出条件へ
    $redirect = "../../die_next_condition.html?press_id=" . $press_id;

} else {
    // (3) 修理 + 条件変更
    // (4) 条件変更のみ
    // → 進捗一覧へ戻る
    $redirect = "../../die_progress_list.html";
}

    
    /* ----------------------------------------
   6. リダイレクト
---------------------------------------- */
header("Location: $redirect");
exit;

?>
