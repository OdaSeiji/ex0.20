<?php
// ビレットの在庫と発注の目安（exd11 PressPlanV12 の中央下の表を移植。考え方は ex0.11 のまま）
// - 在庫：太さごとの最後のビレット在庫更新（t_checkbillet）。VN の列は入れない（ex0.11 と同じ）
// - 実績：期間内に押した本数（t_press。材質は金型の品番から）
// - 計画：期間内の計画の本数（t_press_plan）。太さ・長さは、その品番を最後に押したときのビレット
//         （ex0.11 は同じ日に2回押していると二重に数えていたので、最後の1回だけを使う）
// - 発注：（計画 − 在庫）を 7 本（1束）単位に切り上げ
// 長さはちょうど 600 / 1200 のものだけ数える（ex0.11 と同じ）
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "../billet_check/columns.php";

$start = (string)($_GET["start"] ?? "");
$end   = (string)($_GET["end"] ?? "");
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    echo json_encode(["rows" => []]);
    exit;
}

const MATERIAL_BY_ID = [1 => "6061", 2 => "6063", 3 => "6N01A"];
$sizes = $pdo->query("SELECT id, billet_size FROM m_billet_size ORDER BY billet_size")->fetchAll();

// 表の行（太さ × 材質 × 長さ）
$rows = [];
foreach ($sizes as $sz) {
    foreach (["6061", "6063", "6N01A"] as $m) {
        foreach ([1200, 600] as $len) {
            $rows["{$sz['billet_size']}|{$m}|{$len}"] = [
                "size" => (int)$sz["billet_size"], "material" => $m, "length" => $len,
                "stock" => null, "stock_at" => null, "used" => 0, "plan" => 0, "order" => 0,
            ];
        }
    }
}

// 在庫：太さごとの最後の記録
$st = $pdo->prepare("SELECT * FROM t_checkbillet WHERE billet_size_id = :id ORDER BY check_at DESC, id DESC LIMIT 1");
foreach ($sizes as $sz) {
    $st->execute([":id" => $sz["id"]]);
    $c = $st->fetch();
    if (!$c) continue;
    foreach (BILLET_COLUMNS as [$col, $material, $length, $vn]) {
        $k = "{$sz['billet_size']}|{$material}|{$length}";
        if ($vn || !isset($rows[$k])) continue;
        $rows[$k]["stock"] = $c[$col] === null ? null : (int)$c[$col];
        $rows[$k]["stock_at"] = $c["check_at"];
    }
}

// 実績
$st = $pdo->prepare("
    SELECT p.billet_size, p.billet_length, pn.billet_material_id, SUM(p.actual_billet_quantities) AS qty
    FROM t_press p
    JOIN m_dies d ON d.id = p.dies_id
    LEFT JOIN m_production_numbers pn ON pn.id = d.production_number_id
    WHERE p.press_date_at BETWEEN :s AND :e
    GROUP BY p.billet_size, p.billet_length, pn.billet_material_id
");
$st->execute([":s" => $start, ":e" => $end]);
foreach ($st->fetchAll() as $r) {
    $m = MATERIAL_BY_ID[(int)$r["billet_material_id"]] ?? null;
    $k = "{$r['billet_size']}|{$m}|{$r['billet_length']}";
    if ($m && isset($rows[$k])) $rows[$k]["used"] += (int)$r["qty"];
}

// 計画（品番ごとに、最後に押したときのビレットの太さ・長さ）
$st = $pdo->prepare("
    WITH last_press AS (
        SELECT * FROM (
            SELECT d.production_number_id, p.billet_size, p.billet_length,
                   ROW_NUMBER() OVER (PARTITION BY d.production_number_id ORDER BY p.press_date_at DESC, p.id DESC) AS rn
            FROM t_press p JOIN m_dies d ON d.id = p.dies_id
        ) x WHERE rn = 1
    )
    SELECT lp.billet_size, lp.billet_length, pn.billet_material_id, SUM(pl.quantity) AS qty
    FROM t_press_plan pl
    JOIN m_production_numbers pn ON pn.id = pl.production_number_id
    JOIN last_press lp ON lp.production_number_id = pl.production_number_id
    WHERE pl.plan_date BETWEEN :s AND :e
    GROUP BY lp.billet_size, lp.billet_length, pn.billet_material_id
");
$st->execute([":s" => $start, ":e" => $end]);
foreach ($st->fetchAll() as $r) {
    $m = MATERIAL_BY_ID[(int)$r["billet_material_id"]] ?? null;
    $k = "{$r['billet_size']}|{$m}|{$r['billet_length']}";
    if ($m && isset($rows[$k])) $rows[$k]["plan"] += (int)$r["qty"];
}

// 発注
foreach ($rows as &$r) {
    $need = $r["plan"] - (int)$r["stock"];
    $r["order"] = $need > 0 ? (int)(ceil($need / 7) * 7) : 0;
}
unset($r);

echo json_encode(["rows" => array_values($rows)], JSON_UNESCAPED_UNICODE);
