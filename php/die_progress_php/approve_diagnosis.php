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
   ・t_die_issue と t_die_diagnosis は 1:1 のため、
     既存 open issue がすでに別 diagnosis に紐づいていれば
     新規 issue を作成してリンクする
---------------------------------------- */
if ($should_create_issue && empty($die_issue_id)) {

    /* 7-1. 同じ die_id の open Issue で、どの diagnosis にも紐づいていないものを探す */
    $sql = "
        SELECT i.id FROM t_die_issue i
        WHERE i.die_id = ? AND i.status = 'open'
          AND NOT EXISTS (
              SELECT 1 FROM t_die_diagnosis d WHERE d.die_issue_id = i.id
          )
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$die_id]);
    $free_issue = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($free_issue) {
        /* 7-2. 空き issue にリンク（die_issue_id が NULL のときのみ安全に更新） */
        $upd = $pdo->prepare(
            "UPDATE t_die_diagnosis SET die_issue_id = ? WHERE id = ? AND die_issue_id IS NULL"
        );
        $upd->execute([$free_issue["id"], $diagnosis_id]);

    } else {
        /* 7-3. 空き issue がない（すべて使用済み or 存在しない）→ 新規 Issue を作成 */
        $ins = $pdo->prepare("
            INSERT INTO t_die_issue (die_id, issue_title, issue_detail, priority, status)
            VALUES (?, ?, ?, 3, 'open')
        ");
        $title  = "診断結果による自動登録（診断ID: {$diagnosis_id}）";
        $detail = "診断結果に基づき自動登録されました";
        $ins->execute([$die_id, $title, $detail]);

        $new_issue_id = $pdo->lastInsertId();

        $upd = $pdo->prepare(
            "UPDATE t_die_diagnosis SET die_issue_id = ? WHERE id = ?"
        );
        $upd->execute([$new_issue_id, $diagnosis_id]);
    }
}

/* ----------------------------------------
   8. 完了レスポンス
---------------------------------------- */
echo json_encode([
    "status" => "success",
    "message" => "承認処理が完了しました",
    "redirect" => "/ex0.20/die_progress_list.html"
]);
exit;

?>
