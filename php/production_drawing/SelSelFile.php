<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->prepare("
    SELECT file_url
    FROM m_production_numbers
    WHERE id = :targetId
");
$stmt->execute([':targetId' => (int)$_POST['targetId']]);
echo json_encode($stmt->fetchAll());
