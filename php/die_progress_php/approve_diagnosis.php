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

$diagnosis_id = $diagnosis["id"];
$need_fix     = $diagnosis["need_fix"];        // ★ need_fix を取得
$die_issue_id = $diagnosis["die_issue_id"];

/* ----------------------------------------
   4. die_lifecycle_status_id を取得
---------------------------------------- */
$sql = "SELECT die_lifecycle_status_id FROM m_dies WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$die_id]);
$die_info = $stmt->fetch(PDO::FETCH_ASSOC);

$die_lifecycle_status_id = $die_info["die_lifecycle_status_id"];

/* ----------------------------------------
   5. 承認処理
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
   6. Issue 自動登録の条件判定（新仕様）
   ----------------------------------------
   ▼ t_die_issue に追加する条件（誠司さん仕様）

   ① need_fix = 1（修理が必要）なら登録する

   ② need_fix = 0（修理不要）でも、
      die_lifecycle_status_id ≠ 7（移管済み以外）なら登録する

   ③ need_fix = 0 かつ die_lifecycle_status_id = 7（移管済み）の場合のみ、
      t_die_issue に登録せず運用を続ける

   ※ まとめ：
      「修理が必要でない範囲である金型で、移管済みであれば登録しない。
        そうでないなら t_die_issue で管理を行う」
---------------------------------------- */

$should_create_issue =
    ($need_fix == 1) ||
    ($need_fix == 0 && $die_lifecycle_status_id != 7);

/* ----------------------------------------
   7. Issue 自動登録
---------------------------------------- */
if ($should_create_issue && empty($die_issue_id)) {

    /* 7-1. 同じ die_id の open Issue があるか確認 */
    $sql = "SELECT id FROM t_die_issue 
            WHERE die_id = ? AND status = 'open' 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$die_id]);
    $existing_issue = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_issue) {
        // 既存 issue を紐づける
        $sql = "UPDATE t_die_diagnosis SET die_issue_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$existing_issue["id"], $diagnosis_id]);

    } else {
        /* 7-2. 新規 Issue 作成 */
        $sql = "INSERT INTO t_die_issue 
                (die_id, issue_title, issue_detail, priority, status)
                VALUES (?, ?, ?, 3, 'open')";
        $stmt = $pdo->prepare($sql);

        $title  = "診断結果による自動登録（診断ID: {$diagnosis_id}）";
        $detail = "診断結果に基づき自動登録されました";

        $stmt->execute([$die_id, $title, $detail]);

        $new_issue_id = $pdo->lastInsertId();

        // diagnosis に issue_id を紐づける
        $sql = "UPDATE t_die_diagnosis SET die_issue_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_issue_id, $diagnosis_id]);
    }
}

/* ----------------------------------------
   8. 完了レスポンス
---------------------------------------- */
echo json_encode([
    "status" => "success",
    "message" => "承認処理が完了しました",
    "redirect" => "../../die_progress_list.html"
]);
exit;

?>
