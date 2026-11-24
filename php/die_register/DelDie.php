<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

// 2026-11-24：新規登録（InsDieV2.php）で t_die_handover の本体の行も作るようにしたので、
// 登録を間違えた金型を消せるように、中身が空の本体の行（登録のときに作った行）だけ一緒に消す。
// 発注・輸出・検査・固定資産などの情報が入っている行は消さない → その金型は外部キー（RESTRICT）で消せないまま。
$id = (int)$_POST['id'];

try {
    $pdo->beginTransaction();

    $pdo->prepare("
        DELETE FROM t_die_handover
        WHERE die_id = ?
          AND IFNULL(is_accessory_item_flag, 0) = 0
          AND die_model_code IS NULL AND product_code IS NULL
          AND ordered_at IS NULL AND shipped_at IS NULL AND instruction_created_at IS NULL
          AND inspection_number IS NULL AND inspection_passed_at IS NULL
          AND submitted_to_japan_at IS NULL AND asset_registration_applied_at IS NULL
          AND invoice_number IS NULL AND note2 IS NULL
    ")->execute([$id]);

    $pdo->prepare("DELETE FROM m_dies WHERE id = ?")->execute([$id]);

    $pdo->commit();
    echo json_encode("Deleted");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
