<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$rows = json_decode(file_get_contents("php://input"), true);
if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$fields = [
    "original_table_no",
    "die_planning_phase_steps",
    "arrival_at",
    "vn_production_dimensional_inspection_at",
    "vn_qa_dimensional_inspection_at",
    "submit_dimensional_inspection_to_japan_at",
    "jp_dimensional_inspection_at",
    "jp_dimensional_inspection_document_number",
    "anodizing_quality_check_required_flag",
    "anodizing_quality_check_at",
    "mass_production_trial_at",
    "die_handover_at",
    "mass_production_start_at",
    "production_site_change_notice",
    "dimensional_inspection_by",
    "bcp_flag",
    "die_transfer_ready_flag",
    "memo",
];

foreach ($rows as $d) {
    if (empty($d["die_id"])) continue;

    $vals = [];
    foreach ($fields as $f) {
        $v = $d[$f] ?? null;
        $vals[$f] = ($v === "" || $v === null) ? null : $v;
    }

    if (!empty($d["id"])) {
        // UPDATE
        $sets   = implode(", ", array_map(fn($f) => "$f = ?", $fields));
        $params = array_values($vals);
        $params[] = $d["id"];
        $pdo->prepare("UPDATE t_die_handover_progress SET $sets WHERE id = ?")
            ->execute($params);
    } else {
        // INSERT
        $cols   = implode(", ", $fields);
        $places = implode(", ", array_fill(0, count($fields), "?"));
        $params = array_merge([$d["die_id"]], array_values($vals));
        $pdo->prepare("INSERT INTO t_die_handover_progress (die_id, $cols) VALUES (?, $places)")
            ->execute($params);
    }
}

echo json_encode(["status" => "ok"]);
