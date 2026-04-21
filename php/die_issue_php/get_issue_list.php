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

/*
    die_issue_list.html が必要とするデータ：

    - id
    - die_number
    - issue_title
    - assignee（leave_at IS NULL の現役スタッフのみ）
    - approval_status
    - priority
    - completion_status   ← ★ これが必要！
    - created_at
    - repair_count（t_die_clinical_record）
    - press_cnt（Issue.created_at 以降の t_press の件数）
*/

$sql = "
SELECT 
    i.id,
    d.die_number,
    i.issue_title,

    -- 現役スタッフのみ
    s.staff_name AS assignee,

    i.approval_status,
    i.priority,
    i.completion_status,   -- ★ 追加！
    i.created_at,

    -- 修理回数
    (
        SELECT COUNT(*)
        FROM t_die_clinical_record r
        WHERE r.issue_id = i.id
    ) AS repair_count,

    -- press cnt（created 以降の生産回数）
    (
        SELECT COUNT(*)
        FROM t_press p
        WHERE p.dies_id = i.die_id
          AND p.press_date_at > i.created_at
    ) AS press_cnt

FROM t_die_issue i
LEFT JOIN m_dies d 
    ON i.die_id = d.id
LEFT JOIN m_staff s 
    ON i.reported_by = s.id
    AND s.leave_at IS NULL   -- 退職者は除外

ORDER BY i.id DESC
";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);