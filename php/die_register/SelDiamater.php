<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->query("SELECT id, die_diamater FROM m_dies_diamater ORDER BY die_diamater");
echo json_encode($stmt->fetchAll());
