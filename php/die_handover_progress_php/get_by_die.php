<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$die_id = isset($_GET["die_id"]) ? (int)$_GET["die_id"] : 0;
if (!$die_id) {
    echo json_encode(["error" => "die_id required"]);
    exit;
}

$sql = "
    SELECT
        d.id        AS die_id,
        d.die_number,
        h.id        AS handover_id,
        h.original_table_no,
        h.die_planning_phase_steps,
        h.arrival_at,
        h.vn_production_dimensional_inspection_at,
        h.vn_qa_dimensional_inspection_at,
        h.submit_dimensional_inspection_to_japan_at,
        h.jp_dimensional_inspection_at,
        h.jp_dimensional_inspection_document_number,
        h.anodizing_quality_check_required_flag,
        h.anodizing_quality_check_at,
        h.mass_production_trial_at,
        h.die_handover_at,
        h.mass_production_start_at,
        h.production_site_change_notice,
        h.dimensional_inspection_by,
        h.bcp_flag,
        h.die_transfer_ready_flag,
        h.memo,
        h.updated_at
    FROM m_dies d
    LEFT JOIN t_die_handover_progress h ON h.die_id = d.id
    WHERE d.id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$die_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($row ?: ["error" => "not found"], JSON_UNESCAPED_UNICODE);
