<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$sql = "
    SELECT
        h.id,
        COALESCE(d.die_number, h.die_model_code) AS die_number,
        COALESCE(p.production_number, h.product_code) AS production_number,
        h.ordered_at,
        h.shipped_at
    FROM t_die_handover h
    LEFT JOIN m_dies d ON h.die_id = d.id
    LEFT JOIN m_production_numbers p ON d.production_number_id = p.id
    WHERE h.ordered_at IS NULL OR h.shipped_at IS NULL
    ORDER BY h.id DESC
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
