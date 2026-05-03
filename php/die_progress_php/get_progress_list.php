<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

/*
  進捗一覧 API（前方一致検索対応）

  ◆ 基点：t_press（押出）
  ◆ 金型番号：m_dies.die_number
      t_press.dies_id → m_dies.id

  ◆ 測定：t_die_inspection
      t_die_inspection.die_id = t_press.dies_id

  ◆ 診断：t_die_diagnosis
      t_die_diagnosis.inspection_id = t_die_inspection.id

  ◆ 修理：t_die_fix
      t_die_fix.diagnosis_id = t_die_diagnosis.id

  ◆ pressing_type（〇 / ◎ / ●）
      m_pressing_type.pressing_type

  ◆ 前方一致検索：
      GET / get_progress_list.php?die=D-12
      → WHERE md.die_number LIKE 'D-12%'
*/

/* -------------------------
   検索条件（前方一致）
-------------------------- */
$where = "";
$params = [];

if (!empty($_GET['die'])) {
    $where = "WHERE md.die_number LIKE :die";
    $params[':die'] = $_GET['die'] . '%';
}

/* -------------------------
   メイン SQL
-------------------------- */
$sql = "
SELECT
    p.id AS press_id,
    p.press_date_at AS press_date,

    -- 金型番号
    md.die_number,

    -- 押出種別（記号）
    pt.pressing_type AS pressing_symbol,

    -- 測定済みか？
    CASE WHEN i.id IS NOT NULL THEN 1 ELSE 0 END AS inspected,

    -- 診断済みか？
    CASE WHEN d.id IS NOT NULL THEN 1 ELSE 0 END AS diagnosed,

    -- 承認済みか？
    CASE 
        WHEN d.approval_status = 'approved' THEN 1
        WHEN d.id IS NULL THEN NULL
        ELSE 0
    END AS approved,

    -- 修理要否（ng_action 2 or 3）
    CASE 
        WHEN d.ng_action IN (2,3) THEN 1
        WHEN d.id IS NULL THEN NULL
        ELSE 0
    END AS need_fix,

    -- 修理完了か？
    CASE 
        WHEN f.actual_fix_date IS NOT NULL THEN 1
        WHEN d.ng_action IN (2,3) THEN 0
        ELSE NULL
    END AS fixed

FROM t_press p

-- 金型番号
LEFT JOIN m_dies md
  ON p.dies_id = md.id

-- 押出種別（〇◎●）
LEFT JOIN m_pressing_type pt
  ON p.pressing_type_id = pt.id

-- 測定（押出1回につき測定1回）
LEFT JOIN t_die_inspection i
  ON i.die_id = p.dies_id

-- 診断
LEFT JOIN t_die_diagnosis d
  ON d.inspection_id = i.id

-- 修理
LEFT JOIN t_die_fix f
  ON f.diagnosis_id = d.id

$where
ORDER BY p.press_date_at DESC, p.id DESC
LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
