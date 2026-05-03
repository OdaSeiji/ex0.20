<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$press_id = $_GET['press_id'];

$sql = "
SELECT 
    p.id AS press_id,
    p.dies_id,
    md.die_number,
    p.press_date_at AS press_date,
    p.pressing_type_id,
    mpt.pressing_type,                      -- ★ 記号（〇/◎/●）
    p.actual_billet_quantities as actual_billet_quantity,
    p.press_start_at,
    p.press_finish_at
FROM t_press p
LEFT JOIN m_dies md
  ON p.dies_id = md.id
LEFT JOIN m_pressing_type mpt
  ON p.pressing_type_id = mpt.id
WHERE p.id = :press_id
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":press_id", $press_id, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
