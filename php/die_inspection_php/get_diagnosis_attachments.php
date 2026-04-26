<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$diagnosis_id = $_GET["diagnosis_id"] ?? null;

if (!$diagnosis_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        id,
        diagnosis_id,
        file_name,
        original_name,
        file_type,
        description,
        uploaded_at
    FROM t_die_attachment
    WHERE diagnosis_id = ?
    ORDER BY uploaded_at ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$diagnosis_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
