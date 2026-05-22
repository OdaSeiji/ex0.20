<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

/*
  t_die_inspection の created_at の新しい順に 10 件取得
  die_number, staff_name も JOIN して取得
*/

$sql = "
    SELECT
        i.id,
        i.press_id,
        i.created_at,
        i.overall_result,
        d.die_number,
        s.staff_name
    FROM t_die_inspection i
    LEFT JOIN m_dies d ON i.die_id = d.id
    LEFT JOIN m_staff s ON i.inspection_staff_id = s.id
    ORDER BY i.created_at DESC
    LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
