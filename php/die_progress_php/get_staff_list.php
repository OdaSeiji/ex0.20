<?php
require_once '../db.php';

$sql = "
    SELECT 
        id,
        staff_name
    FROM m_staff
    WHERE leave_at IS NULL      -- ★ 現役のみ
    ORDER BY staff_name
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows);
