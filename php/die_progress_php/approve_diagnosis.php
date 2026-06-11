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
$need_fix     = $diagnosis["need_fix"];
$ng_action    = $diagnosis["ng_action"];
$die_issue_id = $diagnosis["die_issue_id"];

// ng_action 2=修理, 3=修理+条件変更 → need_fix を上書き補正（保存時に未セットの古いデータ対応）
if ($ng_action == 2 || $ng_action == 3) {
    $need_fix = 1;
}

/* ----------------------------------------
   4. die_lifecycle_status_id を取得
---------------------------------------- */
$sql = "SELECT die_lifecycle_status_id, die_condition_id FROM m_dies WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$die_id]);
$die_info = $stmt->fetch(PDO::FETCH_ASSOC);

$die_lifecycle_status_id = $die_info["die_lifecycle_status_id"];
$die_condition_id        = $die_info["die_condition_id"];

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
   6. 修理が必要な場合、金型のコンディションを Trial (1) にセット
---------------------------------------- */
if ($need_fix == 1) {
    $pdo->prepare("UPDATE m_dies SET die_condition_id = 1 WHERE id = ?")
        ->execute([$die_id]);

    $pdo->prepare("
        INSERT INTO t_die_condition_history (die_id, condition_id, changed_at, staff_id, memo)
        VALUES (?, 1, NOW(), 9999, '診断承認により Trial に設定')
    ")->execute([$die_id]);
}

/* ----------------------------------------
   7. 修理不要の場合、フェーズを1段階昇格
   Trial(1) → Mass Production Trial(2)
   Mass Production Trial(2) → Mass Production(3)
   それ以外（NULL, 3）は変更しない
---------------------------------------- */
if ($need_fix == 0 && in_array($die_condition_id, [1, 2])) {
    $next_condition_id = $die_condition_id + 1;

    $pdo->prepare("UPDATE m_dies SET die_condition_id = ? WHERE id = ?")
        ->execute([$next_condition_id, $die_id]);

    $condition_names = [1 => 'Trial', 2 => 'Mass Production Trial', 3 => 'Mass Production'];
    $memo = "診断承認により {$condition_names[$die_condition_id]} → {$condition_names[$next_condition_id]} に昇格";

    $pdo->prepare("
        INSERT INTO t_die_condition_history (die_id, condition_id, changed_at, staff_id, memo)
        VALUES (?, ?, NOW(), 9999, ?)
    ")->execute([$die_id, $next_condition_id, $memo]);
}

/* ----------------------------------------
   9. Issue 自動登録の条件判定（新仕様）
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
   10. Issue 自動登録
   ・die_id に紐づく open な issue があればリンク
   ・なければ新規作成してリンク
   ・1つの issue に複数 diagnosis がリンクされる（issue が close するまで）
---------------------------------------- */
if ($should_create_issue && empty($die_issue_id)) {

    /* 7-1. 同じ die_id の open Issue を探す */
    $sql = "SELECT id FROM t_die_issue WHERE die_id = ? AND status = 'open' LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$die_id]);
    $existing_issue = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_issue) {
        /* 7-2. 既存 open issue にリンク */
        $upd = $pdo->prepare("UPDATE t_die_diagnosis SET die_issue_id = ? WHERE id = ?");
        $upd->execute([$existing_issue["id"], $diagnosis_id]);

    } else {
        /* 7-3. open issue がない → 新規 Issue を作成してリンク */
        $ins = $pdo->prepare("
            INSERT INTO t_die_issue (die_id, issue_title, issue_detail, priority, status)
            VALUES (?, ?, ?, 3, 'open')
        ");
        $title  = "診断結果による自動登録（診断ID: {$diagnosis_id}）";
        $detail = "診断結果に基づき自動登録されました";
        $ins->execute([$die_id, $title, $detail]);

        $new_issue_id = $pdo->lastInsertId();

        $upd = $pdo->prepare("UPDATE t_die_diagnosis SET die_issue_id = ? WHERE id = ?");
        $upd->execute([$new_issue_id, $diagnosis_id]);
    }
}

/* ----------------------------------------
   11. 完了レスポンス
---------------------------------------- */
echo json_encode([
    "status" => "success",
    "message" => "承認処理が完了しました",
    "redirect" => "/ex0.20/die_progress_list.html"
]);
exit;

?>
