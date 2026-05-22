<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$die_id = $_GET["die_id"] ?? null;
if (!$die_id) {
    echo json_encode(["error" => "die_id is required"]);
    exit;
}

$sql = "
    SELECT
        p.id                    AS press_id,
        p.press_date_at         AS press_date,
        p.press_machine_no,
        i.id                    AS inspection_id,
        i.inspection_date,
        i.overall_result,
        d.id                    AS diagnosis_id,
        d.diagnosis_date,
        d.overall_judgement,
        d.repair_required,
        d.approval_status       AS diagnosis_approval,
        d.approval_date         AS diagnosis_approval_date,
        f.id                    AS fix_id,
        f.plan_fix_date,
        f.plan_approval_status,
        f.actual_fix_date,
        f.actual_approval_status
    FROM t_press p
    LEFT JOIN t_die_inspection i ON i.press_id     = p.id
    LEFT JOIN t_die_diagnosis  d ON d.inspection_id = i.id
    LEFT JOIN t_die_fix        f ON f.diagnosis_id  = d.id
    WHERE p.dies_id = ?
    ORDER BY p.press_date_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$die_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
