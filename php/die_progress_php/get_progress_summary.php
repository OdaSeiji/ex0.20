<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

try {
    require_once "../db.php";

    $row = $pdo->query("
        SELECT
            COUNT(CASE WHEN i.id IS NULL
                       THEN 1 END) AS insp_cur,
            COUNT(CASE WHEN p.press_date_at < CURDATE()
                            AND (i.created_at IS NULL OR i.created_at >= CURDATE())
                       THEN 1 END) AS insp_yst,

            COUNT(CASE WHEN i.id IS NOT NULL AND d.id IS NULL
                       THEN 1 END) AS diag_cur,
            COUNT(CASE WHEN i.id IS NOT NULL
                            AND i.created_at < CURDATE()
                            AND (d.created_at IS NULL OR d.created_at >= CURDATE())
                       THEN 1 END) AS diag_yst,

            COUNT(CASE WHEN d.id IS NOT NULL AND d.approval_status = 'pending'
                       THEN 1 END) AS appr_cur,
            COUNT(CASE WHEN d.id IS NOT NULL
                            AND d.created_at < CURDATE()
                            AND (d.approval_date IS NULL OR d.approval_date >= CURDATE())
                       THEN 1 END) AS appr_yst,

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

    function step($cur, $yst) {
        return ["current" => (int)$cur, "yesterday" => (int)$yst, "delta" => (int)$cur - (int)$yst];
    }

    $result = [
        "inspection" => step($row["insp_cur"], $row["insp_yst"]),
        "diagnosis"  => step($row["diag_cur"], $row["diag_yst"]),
        "approval"   => step($row["appr_cur"], $row["appr_yst"]),
        "fix_plan"   => step($row["plan_cur"], $row["plan_yst"]),
        "fix_report" => step($row["rep_cur"],  $row["rep_yst"]),
    ];

} catch (Throwable $e) {
    $result = ["error" => $e->getMessage()];
}

ob_end_clean();
header("Content-Type: application/json; charset=UTF-8");
echo json_encode($result, JSON_UNESCAPED_UNICODE);
