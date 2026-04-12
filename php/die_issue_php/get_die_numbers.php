<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=extrusion;charset=utf8",
    "webuser",
    ""
);

$sql = "SELECT id, die_number FROM m_dies ORDER BY die_number";
$stmt = $pdo->query($sql);

$rows = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        "id" => $r["id"],
        "die_number" => $r["die_number"]
    ];
}

echo json_encode($rows);