<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$file_url = str_replace('#', '', $_POST['file_url']);

$stmt = $pdo->prepare("UPDATE m_production_numbers SET file_url = :file_url WHERE id = :id");
$stmt->execute([
    ':file_url' => $file_url,
    ':id'       => (int)$_POST['targetId'],
]);
echo json_encode("INSERTED");
