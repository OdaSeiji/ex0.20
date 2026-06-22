<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->query("
    SELECT
        id,
        production_number,
        (file_url IS NOT NULL AND file_url <> '') AS has_file
    FROM m_production_numbers
    ORDER BY production_number ASC
");
echo json_encode($stmt->fetchAll());
