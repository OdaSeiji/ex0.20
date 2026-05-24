<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$q = "%" . ($_GET["q"] ?? "") . "%";

$sql = "
    SELECT
        d.id,
        d.die_number,
        d.arrival_at,
        COALESCE(p.production_number, '—') AS production_number
    FROM m_dies d
    LEFT JOIN m_production_numbers p ON d.production_number_id = p.id
    WHERE d.die_number LIKE ?
    AND (d.is_disable IS NULL OR d.is_disable != 1)
    ORDER BY d.die_number
    LIMIT 20
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$q]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
