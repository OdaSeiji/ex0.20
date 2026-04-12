<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=extrusion;charset=utf8",
    "webuser",
    ""
);

$sql = "SELECT id, staff_name FROM m_staff ORDER BY staff_name";
$stmt = $pdo->query($sql);

$rows = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = [
        "id" => $r["id"],
        "name" => $r["staff_name"]
    ];
}

echo json_encode($rows);