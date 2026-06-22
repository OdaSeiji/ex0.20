<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->query("SELECT id, bolster_name FROM m_bolster ORDER BY bolster_name");
echo json_encode($stmt->fetchAll());
