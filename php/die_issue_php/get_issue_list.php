<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=extrusion;charset=utf8",
    "webuser",
    ""
);

$sql = "
SELECT 
    i.id,
    d.die_number,
    i.issue_title,
    i.issue_description,
    s.staff_name AS assignee_name,
    i.approval_status,
    i.created_at,
    (
        SELECT COUNT(*) 
        FROM t_die_clinical_record cr 
        WHERE cr.issue_id = i.id
    ) AS repair_count
FROM t_die_issue i
LEFT JOIN m_dies d ON i.die_id = d.id
LEFT JOIN m_staff s ON i.assignee_id = s.id
ORDER BY i.id DESC
";

$stmt = $pdo->query($sql);

$rows = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = $r;
}

echo json_encode($rows);