<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
SELECT
    iss.id                                              AS issue_id,
    iss.die_id,
    md.die_number,
    COALESCE(pn.production_number, '—')                 AS production_number,
    iss.issue_title_jp,
    iss.priority,
    DATE(iss.created_at)                                AS issue_created_at,
    DATEDIFF(NOW(), iss.created_at)                     AS issue_days,

    md.die_condition_id,
    mc.name                                             AS die_condition_name,

    diag.need_fix,
    diag.ng_action,
    diag.advance_condition,
    diag.approval_status                                AS diag_approval_status,
    DATE(diag.approval_date)                            AS diag_approved_at,

    fix.plan_fix_date,
    fix.plan_approval_status,
    fix.actual_fix_date,
    fix.actual_approval_status,

    (
        SELECT MIN(p.press_date_at)
        FROM t_press p
        WHERE p.dies_id = iss.die_id
          AND p.press_date_at > diag.approval_date
    ) AS first_press_after_approval,

    NULLIF(GREATEST(
        COALESCE(DATE(diag.approval_date), '2000-01-01'),
        COALESCE(fix.plan_fix_date,        '2000-01-01'),
        COALESCE(fix.actual_fix_date,      '2000-01-01')
    ), '2000-01-01')                                    AS last_action_date,

    DATEDIFF(NOW(), COALESCE(
        NULLIF(GREATEST(
            COALESCE(DATE(diag.approval_date), '2000-01-01'),
            COALESCE(fix.plan_fix_date,        '2000-01-01'),
            COALESCE(fix.actual_fix_date,      '2000-01-01')
        ), '2000-01-01'),
        DATE(iss.created_at)
    ))                                                  AS days_since_last_action,

    CASE
        WHEN diag.id IS NULL
            THEN '① 診断未完了'
        WHEN diag.approval_status IS NULL OR diag.approval_status = 'pending'
            THEN '② 診断承認待ち'
        WHEN diag.approval_status = 'rejected'
            THEN '③ 診断却下'
        WHEN diag.advance_condition = 1 AND (
            SELECT COUNT(*) FROM t_press p
            WHERE p.dies_id = iss.die_id
              AND p.press_date_at > diag.approval_date
        ) = 0
            THEN '④ ステージUP承認済・次押出待ち'
        WHEN diag.need_fix = 1 AND fix.id IS NULL
            THEN '⑤ 修理計画未作成'
        WHEN diag.need_fix = 1 AND fix.plan_approval_status != 'approved'
            THEN '⑥ 修理計画承認待ち'
        WHEN diag.need_fix = 1 AND fix.actual_fix_date IS NULL
            THEN '⑦ 修理実施待ち'
        WHEN diag.need_fix = 1 AND fix.actual_approval_status != 'approved'
            THEN '⑧ 修理報告承認待ち'
        ELSE '⑨ 完了待ち（Issue未クローズ）'
    END                                                 AS current_phase

FROM t_die_issue iss
JOIN m_dies md
    ON iss.die_id = md.id
LEFT JOIN m_die_conditions mc
    ON md.die_condition_id = mc.id
LEFT JOIN m_production_numbers pn
    ON md.production_number_id = pn.id
LEFT JOIN (
    SELECT *
    FROM t_die_diagnosis
    WHERE id IN (
        SELECT MAX(id)
        FROM t_die_diagnosis
        GROUP BY die_issue_id
    )
) diag ON diag.die_issue_id = iss.id
LEFT JOIN t_die_fix fix
    ON fix.diagnosis_id = diag.id

WHERE iss.status = 'open'

ORDER BY
    days_since_last_action DESC,
    issue_days DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
