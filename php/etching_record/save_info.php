<?php
require_once __DIR__ . "/common.php";

// エッチング評価記録：押出ごとの項目を保存する。変えるたびに呼ぶ
//   POST JSON: { press_id, check_date: "YYYY-MM-DD" | null, finish: 0〜4, staff_id: m_staff.id | null, check_staff_id: m_staff.id | null, note }
//   エッチングの日は t_press.etching_check_date、ほかは t_press_sub

$body         = srJsonBody();
$pressId      = (int)($body["press_id"] ?? 0);
$checkDate    = $body["check_date"] ?? null;
$finish       = (int)($body["finish"] ?? 0);
$staffId      = isset($body["staff_id"]) ? (int)$body["staff_id"] : null;
$checkStaffId = isset($body["check_staff_id"]) ? (int)$body["check_staff_id"] : null;
$note         = trim((string)($body["note"] ?? ""));

if ($pressId <= 0) srFail(400, "press_id が不正です");
if (!in_array($finish, ER_FINISHES, true)) srFail(400, "状態の値が不正です");
if (mb_strlen($note) > 1000) srFail(400, "メモは1000文字までです");

try {
    if (!srPressInfo($pdo, $pressId)) srFail(404, "押出が見つかりません");
    srCheckMeasureDate($pdo, $checkDate);
    $chk = $pdo->prepare("SELECT 1 FROM m_staff WHERE id = :id");
    foreach ([$staffId, $checkStaffId] as $sid) {
        if ($sid === null) continue;
        $chk->execute([":id" => $sid]);
        if (!$chk->fetchColumn()) srFail(400, "担当者が見つかりません");
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE t_press SET etching_check_date = :d WHERE id = :id");
    $stmt->bindValue(":d", $checkDate, $checkDate === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":id", $pressId, PDO::PARAM_INT);
    $stmt->execute();
    srSetPressSub($pdo, $pressId, "etching_finish", $finish);
    srSetPressSub($pdo, $pressId, "etching_staff", $staffId);
    srSetPressSub($pdo, $pressId, "etching_check_staff", $checkStaffId);
    srSetPressSub($pdo, $pressId, "etching_note", $note === "" ? null : $note);
    $pdo->commit();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    srFail(500, $e->getMessage());
}
