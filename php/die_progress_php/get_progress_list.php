<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

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
   メイン SQL（8ステップ対応版）
-------------------------- */
$sql = "
SELECT
    p.id AS press_id,
    p.press_date_at AS press_date,

    -- 金型番号
    md.die_number,

    -- 押出種別（記号）
    pt.pressing_type AS pressing_symbol,

    /* ============================
       ① 測定（t_die_inspection）
    ============================ */
    CASE 
        WHEN i.id IS NOT NULL THEN 1
        ELSE NULL
    END AS inspected,

    /* ============================
       ② 診断（t_die_diagnosis）
    ============================ */
    CASE 
        WHEN d.id IS NOT NULL THEN 1
        ELSE NULL
    END AS diagnosed,

    /* ============================
       ③ 修理要否（need_fix）
       2,3 → 修理必要
    ============================ */
    CASE 
        WHEN d.ng_action IN (2,3) THEN 1
        WHEN d.id IS NULL THEN NULL
        ELSE 0
    END AS need_fix,

    /* ★ ng_action の生値を返す（追加） */
    d.ng_action,

    /* ============================
       ④ 診断承認（approval_status）
    ============================ */
    CASE
        WHEN d.approval_status = 'approved' THEN 1
        WHEN d.approval_status = 'rejected' THEN -1
        WHEN d.approval_status = 'pending' THEN 0
        ELSE NULL
    END AS approved,

    /* ============================
       ⑤ 修理計画（plan_fix_date）
    ============================ */
    CASE
        WHEN f.plan_fix_date IS NOT NULL THEN 1
        WHEN d.ng_action IN (2,3) THEN 0
        ELSE NULL
    END AS fix_plan,

    /* ============================
       ⑥ 修理計画承認（plan_approval_status）
    ============================ */
    CASE
        WHEN f.plan_approval_status = 'approved' THEN 1
        WHEN f.plan_approval_status = 'pending' THEN 0
        ELSE NULL
    END AS fix_plan_approval,

    /* ============================
       ⑦ 修理報告（actual_fix_date）
    ============================ */
    CASE
        WHEN f.actual_fix_date IS NOT NULL THEN 1
        WHEN d.ng_action IN (2,3) THEN 0
        ELSE NULL
    END AS fix_report,

    /* ============================
       ⑧ 修理報告承認（actual_approval_status）
    ============================ */
    CASE
        WHEN f.actual_approval_status = 'approved' THEN 1
        WHEN f.actual_approval_status = 'pending' THEN 0
        ELSE NULL
    END AS fix_report_approval,

    -- 重点管理フラグ（active な t_die_watch があれば priority を返す）
    w.priority AS watch_priority

FROM t_press p

-- 金型番号
LEFT JOIN m_dies md
  ON p.dies_id = md.id

-- 押出種別（〇◎●）
LEFT JOIN m_pressing_type pt
  ON p.pressing_type_id = pt.id

-- 重点管理（同じ die_id で active なレコードが存在するか）
LEFT JOIN (
    SELECT die_id, priority
    FROM t_die_watch
    WHERE status = 'active'
    GROUP BY die_id
) w ON md.id = w.die_id

-- 測定
LEFT JOIN t_die_inspection i
  ON i.press_id = p.id

-- 診断
LEFT JOIN t_die_diagnosis d
  ON d.inspection_id = i.id

-- 修理（計画・報告・承認すべて含む）
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
