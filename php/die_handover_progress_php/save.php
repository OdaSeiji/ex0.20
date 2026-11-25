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
    // "production_site_change_notice" … 切替業連 No. は品番ごと。下で m_production_numbers.change_notice_no に保存する（2026-11-25）
    "dimensional_inspection_by",
    "bcp_flag",
    "die_transfer_ready_flag",
    "memo",
];

// 切替業連 No.（品番ごと）：送られてきた金型の品番と、今の値を先に読んでおく。
//   編集画面に同じ品番の金型が2つ以上あると、変えていない方も古い値を送ってくるので、
//   今の値と違うものだけを保存する（変えた方が勝つ）。
$noticePn  = [];   // die_id => pn_id
$noticeNow = [];   // pn_id  => 保存する前の値（比べるのはいつもこの値。書いたあとも変えない）
$dieIds = array_values(array_filter(array_map(
    fn($d) => array_key_exists("production_site_change_notice", $d) ? (int)($d["die_id"] ?? 0) : 0, $rows)));
if ($dieIds) {
    $in = implode(",", array_fill(0, count($dieIds), "?"));
    $st = $pdo->prepare("
        SELECT d.id AS die_id, pn.id AS pn_id, pn.change_notice_no
        FROM m_dies d JOIN m_production_numbers pn ON pn.id = d.production_number_id
        WHERE d.id IN ($in)");
    $st->execute($dieIds);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $noticePn[(int)$r["die_id"]] = (int)$r["pn_id"];
        $noticeNow[(int)$r["pn_id"]] = $r["change_notice_no"];
    }
}
$updNotice = $pdo->prepare("UPDATE m_production_numbers SET change_notice_no = ? WHERE id = ?");

foreach ($rows as $d) {
    if (empty($d["die_id"])) continue;

    // 切替業連 No.：品番に保存（品番の無い金型は保存できないので無視）
    if (array_key_exists("production_site_change_notice", $d) && isset($noticePn[(int)$d["die_id"]])) {
        $pnId = $noticePn[(int)$d["die_id"]];
        $v = trim((string)($d["production_site_change_notice"] ?? ""));
        $v = $v === "" ? null : $v;
        if ($v !== $noticeNow[$pnId]) {
            $updNotice->execute([$v, $pnId]);
        }
    }

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
