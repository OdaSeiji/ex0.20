<?php
header("Content-Type: application/json; charset=UTF-8");
// require_once "../../config.php";  // ← パスは環境に合わせて調整してください
require_once '../db.php';

try {
    // POST 受け取り
    $diagnosis_id = $_POST["diagnosis_id"] ?? null;
    $approver_id  = $_POST["approver_id"] ?? null;
    $reject_reason = $_POST["reject_reason"] ?? null;

    if (!$diagnosis_id || !$approver_id || !$reject_reason) {
        echo json_encode([
            "status" => "error",
            "message" => "必要なデータが不足しています"
        ]);
        exit;
    }

    // DB 接続
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 却下処理（status = 2）
    $sql = "
        UPDATE t_die_diagnosis
        SET 
            status = 2,
            reject_reason = :reject_reason,
            approver_id = :approver_id,
            approved_at = NOW()
        WHERE diagnosis_id = :diagnosis_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":reject_reason", $reject_reason, PDO::PARAM_STR);
    $stmt->bindValue(":approver_id", $approver_id, PDO::PARAM_INT);
    $stmt->bindValue(":diagnosis_id", $diagnosis_id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        "status" => "success",
        "message" => "却下処理が完了しました"
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}