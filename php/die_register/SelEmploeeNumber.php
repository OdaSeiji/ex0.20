<?php
require_once '../db.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->query("SELECT id, emploee_number, position_id FROM m_staff");
echo json_encode($stmt->fetchAll());
