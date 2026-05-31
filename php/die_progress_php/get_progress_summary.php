<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$row = $pdo->query("
    SELECT
        -- 1. 測定未完了
        COUNT(CASE WHEN i.id IS NULL
                   THEN 1 END) AS insp_cur,
        COUNT(CASE WHEN p.press_date_at < CURDATE()
                        AND (i.created_at IS NULL OR i.created_at >= CURDATE())
                   THEN 1 END) AS insp_yst,

        -- 2. 診断未完了（測定済み・診断なし）
        COUNT(CASE WHEN i.id IS NOT NULL AND d.id IS NULL
                   THEN 1 END) AS diag_cur,
        COUNT(CASE WHEN i.id IS NOT NULL
                        AND i.created_at < CURDATE()
                        AND (d.created_at IS NULL OR d.created_at >= CURDATE())
                   THEN 1 END) AS diag_yst,

        -- 3. 承認未完了（診断済み・承認待ち）
        COUNT(CASE WHEN d.id IS NOT NULL AND d.approval_status = 'pending'
                   THEN 1 END) AS appr_cur,
        COUNT(CASE WHEN d.id IS NOT NULL
                        AND d.created_at < CURDATE()
                        AND (d.approval_date IS NULL OR d.approval_date >= CURDATE())
                   THEN 1 END) AS appr_yst,

        -- 4. 修理計画未完了（NG承認済み・計画なし）
        COUNT(CASE WHEN d.id IS NOT NULL
                        AND d.ng_action IN (2,3)
                        AND d.approval_status = 'approved'
                        AND f.id IS NULL
                   THEN 1 END) AS plan_cur,
        COUNT(CASE WHEN d.id IS NOT NULL
                        AND d.ng_action IN (2,3)
                        AND d.approval_date IS NOT NULL
                        AND d.approval_date < CURDATE()
                        AND (f.created_at IS NULL OR f.created_at >= CURDATE())
                   THEN 1 END) AS plan_yst,

        -- 5. 修理報告未完了（計画あり・報告なし）
        COUNT(CASE WHEN f.id IS NOT NULL
                        AND f.plan_fix_date IS NOT NULL
                        AND f.actual_fix_reported_at IS NULL
                   THEN 1 END) AS rep_cur,
        COUNT(CASE WHEN f.id IS NOT NULL
                        AND f.created_at < CURDATE()
                        AND (f.actual_fix_reported_at IS NULL OR f.actual_fix_reported_at >= CURDATE())
                   THEN 1 END) AS rep_yst

    FROM t_press p
    LEFT JOIN t_die_inspection i  ON i.press_id      = p.id
    LEFT JOIN t_die_diagnosis  d  ON d.inspection_id = i.id
    LEFT JOIN t_die_fix        f  ON f.diagnosis_id  = d.id
    WHERE p.press_date_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
")->fetch(PDO::FETCH_ASSOC);

function step(int $cur, int $yst): array {
    return ["current" => $cur, "yesterday" => $yst, "delta" => $cur - $yst];
}

echo json_encode([
    "inspection" => step((int)$row["insp_cur"], (int)$row["insp_yst"]),
    "diagnosis"  => step((int)$row["diag_cur"], (int)$row["diag_yst"]),
    "approval"   => step((int)$row["appr_cur"], (int)$row["appr_yst"]),
    "fix_plan"   => step((int)$row["plan_cur"], (int)$row["plan_yst"]),
    "fix_report" => step((int)$row["rep_cur"],  (int)$row["rep_yst"]),
], JSON_UNESCAPED_UNICODE);
