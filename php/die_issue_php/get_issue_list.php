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

$sql = "
SELECT 
    i.id,
    d.die_number,
    i.issue_title,

    -- 担当者は reported_by を使用
    s.staff_name AS assignee,

    i.approval_status,
    i.priority,
    i.created_at,

    (SELECT COUNT(*) FROM t_die_clinical_record r WHERE r.issue_id = i.id) AS repair_count

FROM t_die_issue i
LEFT JOIN m_dies d ON i.die_id = d.id
LEFT JOIN m_staff s ON i.reported_by = s.id
ORDER BY i.id DESC
";

$stmt = $pdo->query($sql);

if ($stmt === false) {
    echo json_encode(["sql_error" => $pdo->errorInfo()]);
    exit;
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);