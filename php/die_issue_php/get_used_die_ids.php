<?php
header("Content-Type: application/json");

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=extrusion;charset=utf8",
        "webuser",
        ""
    );
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

$sql = "SELECT die_id FROM t_die_issue";
$stmt = $pdo->query($sql);

$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($rows);