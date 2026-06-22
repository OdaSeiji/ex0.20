<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->prepare("DELETE FROM m_dies WHERE id = :id");
$stmt->execute([':id' => (int)$_POST['id']]);
echo json_encode("Deleted");
