<?php
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$issue_id = $data['issue_id'];

try {
    $pdo->beginTransaction();

    // ① t_die_issue を正式完了に更新
    $sql1 = "UPDATE t_die_issue
             SET completion_status = 'completed',
                 updated_at = NOW()
             WHERE id = ?";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([$issue_id]);

    // ② 金型状態を normal に戻す
    $sql2 = "UPDATE m_dies
             SET die_condition = 'normal'
             WHERE id = (SELECT die_id FROM t_die_issue WHERE id = ?)";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$issue_id]);

    $pdo->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}