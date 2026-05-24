<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$sql = "
    SELECT
        id,
        emploee_number,
        staff_name,
        role,
        leave_at,
        CASE WHEN password IS NOT NULL THEN 1 ELSE 0 END AS has_password
    FROM m_staff
    ORDER BY (leave_at IS NOT NULL) ASC, emploee_number ASC
";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
