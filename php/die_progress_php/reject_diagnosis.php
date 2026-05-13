<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';   // ★ ここで $pdo が作られる

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

    // ★ db.php が $pdo を作っているので、ここでは何もしない
    // $pdo はそのまま使える

    // 却下処理（approval_status = 'rejected'）
    $sql = "
        UPDATE t_die_diagnosis
        SET 
            approval_status = 'rejected',
            reject_reason = :reject_reason,
            approver_id = :approver_id,
            approval_date = NOW()
        WHERE id = :diagnosis_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":reject_reason", $reject_reason, PDO::PARAM_STR);
    $stmt->bindValue(":approver_id", $approver_id, PDO::PARAM_STR); // VARCHAR(50)
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
