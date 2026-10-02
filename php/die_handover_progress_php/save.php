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
    // "arrival_at" … 到着日は引き継ぎ一覧（t_die_handover.die_arrived_at）を正とするので、ここでは保存しない
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

    // 送られてきた項目だけを保存する（インライン編集は1項目だけ送るため、
    // 送られていない項目まで NULL で上書きすると、ほかの入力済みの項目が消えてしまう）
    $vals = [];
    foreach ($fields as $f) {
        if (!array_key_exists($f, $d)) continue;
        $v = $d[$f];
        $vals[$f] = ($v === "" || $v === null) ? null : $v;
    }
    if (!$vals && !empty($d["id"])) continue;

    if (!empty($d["id"])) {
        // UPDATE
        $sets   = implode(", ", array_map(fn($f) => "$f = ?", array_keys($vals)));
        $params = array_values($vals);
        $params[] = $d["id"];
        $pdo->prepare("UPDATE t_die_handover_progress SET $sets WHERE id = ?")
            ->execute($params);
    } elseif (!$vals) {
        // 項目なしの INSERT（行だけ作る）
        $pdo->prepare("INSERT INTO t_die_handover_progress (die_id) VALUES (?)")->execute([$d["die_id"]]);
    } else {
        // INSERT
        $cols   = implode(", ", array_keys($vals));
        $places = implode(", ", array_fill(0, count($vals), "?"));
        $params = array_merge([$d["die_id"]], array_values($vals));
        $pdo->prepare("INSERT INTO t_die_handover_progress (die_id, $cols) VALUES (?, $places)")
            ->execute($params);
    }
}

echo json_encode(["status" => "ok"]);
