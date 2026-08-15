<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$rows = $pdo->query("
    SELECT id, staff_name
    FROM m_staff
    WHERE leave_at IS NULL AND role = 'operator'
    ORDER BY staff_name
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
