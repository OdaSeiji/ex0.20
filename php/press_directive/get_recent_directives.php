<?php
// 入力済みリスト：全金型の押出指示書を、入力した順（新しい順）に30件
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$stmt = $pdo->query("
    SELECT t.id, t.plan_date_at, t.created_at, t.press_machine, d.die_number,
           t.pressing_type_id, t.billet_input_quantity, t.entry_source
    FROM t_press_directive t
    LEFT JOIN m_dies d ON d.id = t.dies_id
    ORDER BY t.id DESC
    LIMIT 30
");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
