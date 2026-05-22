<?php
require_once "./../db.php";

/*
  t_die_issue の一覧を返す API
  issue_detail を含めて返すように修正済み
*/

$sql = "
    SELECT 
        i.id,
        i.die_id,
        d.die_number,
        i.issue_title,
        i.issue_title_jp,
        i.issue_title_vn,
        i.issue_detail,
        i.priority,
        CASE 
            WHEN i.status = 'open' THEN '未完了'
            ELSE '完了'
        END AS completion_status,
        i.created_at,
        (
            SELECT p.press_date_at
            FROM t_press p
            WHERE p.dies_id = i.die_id
            ORDER BY p.press_date_at DESC
            LIMIT 1
        ) AS latest_press_date,
        CASE 
            WHEN i.priority = 1 THEN '高'
            WHEN i.priority = 2 THEN '中'
            ELSE '低'
        END AS priority_label
    FROM t_die_issue i
    JOIN m_dies d ON i.die_id = d.id
    ORDER BY i.created_at DESC
";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: application/json; charset=UTF-8");
echo json_encode($data);
