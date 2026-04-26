<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

/*
  診断一覧を取得する SQL
  - 診断日
  - 金型番号
  - 検査日
  - 診断者名
  - 診断結果
  - 承認状態
*/

$sql = "
    SELECT 
        d.id,
        d.inspection_id,
        d.diagnosis_date,
        d.diagnosis_result,
        d.diagnosis_comment,
        d.approval_status,
        d.approved_by,
        d.approved_at,

        s.staff_name,
        i.inspection_date,
        m.die_number

    FROM t_die_diagnosis d
    LEFT JOIN m_staff s ON d.diagnosed_by = s.id
    LEFT JOIN t_die_inspection i ON d.inspection_id = i.id
    LEFT JOIN m_dies m ON i.die_id = m.id

    ORDER BY d.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
