<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$sql = "
  SELECT 
      i.id,
      i.inspection_date,
      d.die_number,
      i.inspection_result,
      i.dimension_result,
      i.shape_result,
      s.staff_name,

      /* ★ 診断済みフラグ（1件でもあれば1） */
      (SELECT COUNT(*) FROM t_die_diagnosis dd 
          WHERE dd.inspection_id = i.id) AS diagnosis_count

  FROM t_die_inspection i
  LEFT JOIN m_dies d ON i.die_id = d.id
  LEFT JOIN m_staff s ON i.inspected_by = s.id
  ORDER BY i.inspection_date DESC;
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
