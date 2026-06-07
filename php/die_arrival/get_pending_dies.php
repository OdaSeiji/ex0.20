<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$sql = "
    SELECT
        d.id,
        d.die_number,
        d.budget_id,
        p.production_number,
        d.created_at,
        h.shipped_at
    FROM m_dies d
    LEFT JOIN m_production_numbers p ON d.production_number_id = p.id
    LEFT JOIN t_die_handover h ON h.die_id = d.id
    WHERE d.arrival_at IS NULL
      AND d.is_disable IS NULL
    ORDER BY d.id DESC
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
