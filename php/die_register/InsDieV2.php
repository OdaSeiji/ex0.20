<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

// m_dies に登録したら、t_die_handover の本体の行も一緒に作る（2026-11-24）。
// 作らないと t_die_handover に die_id が無い「1対0」の金型になる
// （documents/2026-11-15_die_tables_reorganization.md 4章）。CSV 取り込み（transfer_to_dies.php）と同じ。
$arrival = trim($_POST['arrival_date'] ?? '');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO m_dies (die_number, production_number_id, die_diamater_id,
            bolstar_id, hole, arrival_at, created_at, die_postition)
        VALUES (:die_number, :production_number_id, :die_diamater_id,
            :bolstar_id, :hole, :arrival_at, :created_at, :die_postition)
    ");
    $stmt->execute([
        ':die_number'          => $_POST['die_number'],
        ':die_postition'       => $_POST['die_postition'],
        ':production_number_id'=> (int)$_POST['production_number_id'],
        ':die_diamater_id'     => (int)$_POST['die_diamater__select'],
        ':bolstar_id'          => (int)$_POST['bolster__select'],
        ':hole'                => (int)$_POST['whole__input'],
        ':arrival_at'          => $arrival !== '' ? $arrival : null,   // 空のときは NULL（'' だと日付エラーで登録できなかった）
        ':created_at'          => $_POST['today'],
    ]);
    $dieId = (int)$pdo->lastInsertId();

    // 本体の行（is_accessory_item_flag = 0）。到着日は、到着日の置き場所を t_die_handover に移す予定なので、ここにも入れる
    $pdo->prepare("INSERT INTO t_die_handover (die_id, is_accessory_item_flag, die_arrived_at) VALUES (?, 0, ?)")
        ->execute([$dieId, $arrival !== '' ? $arrival : null]);

    $pdo->commit();
    echo json_encode("INSERTED");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
