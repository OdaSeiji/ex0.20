<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$response = [
    "press_list" => [],
    "staff_list" => []
];

try {
    // ▼ 過去7日間の押出記録 + 金型番号を JOIN
    $sql_press = "
        SELECT 
            t_press.id,
            t_press.press_date_at,
            m_dies.die_number
        FROM t_press
        JOIN m_dies ON t_press.dies_id = m_dies.id
        WHERE t_press.press_date_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY t_press.press_date_at DESC
    ";
    $stmt = $pdo->prepare($sql_press);
    $stmt->execute();
    $response["press_list"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ▼ 在籍スタッフ（leave_at が NULL）
    $sql_staff = "
        SELECT id, staff_name
        FROM m_staff
        WHERE leave_at IS NULL
        ORDER BY staff_name
    ";
    $stmt = $pdo->prepare($sql_staff);
    $stmt->execute();
    $response["staff_list"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
