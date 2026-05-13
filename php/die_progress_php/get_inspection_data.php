<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$press_id = $_GET["press_id"] ?? null;

if (!$press_id) {
    echo json_encode(["status" => "error", "message" => "press_id is required"]);
    exit;
}

$sql = "
SELECT
    i.id AS inspection_id,
    i.press_id,
    i.inspection_date,
    i.dimension_result,
    i.shape_result,
    i.overall_result,
    i.memo,

    d.die_number,
    p.press_machine_no,
    s.staff_name AS inspection_staff

FROM t_die_inspection i
LEFT JOIN m_dies d ON i.die_id = d.id
LEFT JOIN t_press p ON i.press_id = p.id
LEFT JOIN m_staff s ON i.inspection_staff_id = s.id
WHERE i.press_id = :press_id
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":press_id", $press_id, PDO::PARAM_INT);
$stmt->execute();
$inspection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inspection) {
    echo json_encode(["status" => "error", "message" => "no inspection found"]);
    exit;
}

/* 添付ファイル取得 */
$sql2 = "
SELECT
    id,
    file_path,
    file_type
FROM t_die_attachment
WHERE inspection_id = :inspection_id
";

$stmt2 = $pdo->prepare($sql2);
$stmt2->bindValue(":inspection_id", $inspection["inspection_id"], PDO::PARAM_INT);
$stmt2->execute();
$files = $stmt2->fetchAll(PDO::FETCH_ASSOC);

/* 返却 */
echo json_encode([
    "status" => "success",
    "inspection" => $inspection,
    "inspection_files" => $files
], JSON_UNESCAPED_UNICODE);
