<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$status = $_GET["status"] ?? "active";
$allowed = ["active", "closed", "all"];
if (!in_array($status, $allowed)) $status = "active";

$where = $status === "all" ? "" : "WHERE w.status = ?";

$sql = "
    SELECT
        w.id,
        w.die_id,
        d.die_number,
        COALESCE(p.production_number, '—') AS production_number,
        d.arrival_at,
        w.reason_jp,
        w.reason_vn,
        w.priority,
        w.target_date,
        w.status,
        w.memo,
        w.created_at,
        w.closed_at,
        s.staff_name AS registered_by_name,
        d.die_condition_id,
        dc.name AS die_condition_name
    FROM t_die_watch w
    JOIN m_dies d ON w.die_id = d.id
    LEFT JOIN m_production_numbers p ON d.production_number_id = p.id
    LEFT JOIN m_staff s ON w.registered_by = s.id
    LEFT JOIN m_die_conditions dc ON d.die_condition_id = dc.id
    {$where}
    ORDER BY
        FIELD(w.priority, 'high', 'middle', 'low'),
        w.target_date ASC,
        w.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($status === "all" ? [] : [$status]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
