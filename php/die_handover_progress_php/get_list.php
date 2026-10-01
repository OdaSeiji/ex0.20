<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
    SELECT
        d.id        AS die_id,
        d.die_number,
        h.id,
        h.original_table_no,
        h.die_planning_phase_steps,
        ha.die_arrived_at AS arrival_at,   -- t_die_handover_progress.arrival_at は使わない（列は残す）
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
    -- 到着日は引き継ぎ一覧（t_die_handover）を正とする。同じ型で複数行あるとき（型＋部品）は最初に入力された行
    LEFT JOIN (
        SELECT th.die_id, th.die_arrived_at
        FROM t_die_handover th
        JOIN (SELECT die_id, MIN(id) AS first_id FROM t_die_handover GROUP BY die_id) f ON f.first_id = th.id
    ) ha ON ha.die_id = d.id
    ORDER BY d.die_number
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
