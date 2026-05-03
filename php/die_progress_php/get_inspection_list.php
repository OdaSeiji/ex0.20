<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

/*
  測定一覧を返す SQL のポイント：
  - t_die_inspection を基点に
  - 金型番号（m_dies）
  - 測定者（m_staff）
  - 添付ファイルの有無（t_die_attachment）
*/

$sql = "
SELECT
    i.id,
    i.inspection_date,
    d.die_number,
    s.staff_name,
    i.dimension_result AS dimension,
    i.shape_result AS shape,
    i.inspection_result AS overall,

    -- 添付ファイルの有無
    CASE WHEN att.cnt > 0 THEN 1 ELSE 0 END AS has_file

FROM t_die_inspection i

LEFT JOIN m_dies d
  ON i.die_id = d.id

LEFT JOIN m_staff s
  ON i.inspected_by = s.id

LEFT JOIN (
    SELECT inspection_id, COUNT(*) AS cnt
    FROM t_die_attachment
    GROUP BY inspection_id
) att
  ON att.inspection_id = i.id

ORDER BY i.inspection_date DESC, i.id DESC
LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
