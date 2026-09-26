<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// 品質サマリー（exd11 QualitySummaryV9 の SelSummaryV9.php / SelSummaryTermV9.php を統合して移植）
//   押出1回（t_press 1行）ごとに、本数・NG（コード別）・各工程のチェック日・生産性をまとめて返す（最大400件）
//
// V9 からの変更点
//   - 生産性・総重量に使う品番（比重・製品長さ）を、押出の品番バリアント
//     （t_press.die_production_number_variant_id → m_die_production_number_variants.production_number_id）から取る。
//     バリアントが無い押出は従来どおり型番の既定品番（m_dies.production_number_id）。
//   - 集計に使っていない t_press_work_length_quantity / t_time_press の素の JOIN を削除（結果は同じ）
//   - 号機をプレースホルダーでバインド（V9 は SQL に直接埋め込み）
//   - 正味押出時間が0以下の押出は、生産性・サイクルタイム・総時間を空欄にする（V9 はマイナス値のまま計算）
//
// パラメータ（GET）: die_number（前方一致, 省略可）, machine（0=全号機）, from, to（押出日, 省略可）

$isDate = fn($s) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);

$dieNumber = trim($_GET["die_number"] ?? "");
$machine   = (int)($_GET["machine"] ?? 0);
$from      = $_GET["from"] ?? "";
$to        = $_GET["to"] ?? "";

// NGコード（V9 と同じ並び）
$ngCodes = array_merge(range(301, 324), [351, 401]);
$ngSums = implode(",\n", array_map(fn($c) =>
    "            SUM(CASE WHEN c.quality_code = {$c} THEN q.ng_quantities ELSE 0 END) AS code_{$c}", $ngCodes));
$ngCols = implode(",\n", array_map(fn($c) => "    t10.code_{$c}", $ngCodes));

$where  = ["m_dies.die_number LIKE :die_number"];
$params = [":die_number" => $dieNumber . "%"];
if ($machine > 0) {
    $where[] = "t_press.press_machine_no = :machine";
    $params[":machine"] = $machine;
}
if ($isDate($from) && $isDate($to)) {
    $where[] = "t_press.press_date_at BETWEEN :from AND :to";
    $params[":from"] = $from;
    $params[":to"]   = $to;
}
$whereSql = implode("\n  AND ", $where);

// 正味押出時間（秒）＝ 終了 − 開始 − 停止（コード700以外）
//   0以下（終了が開始より前などの入力ミス。2024年以降5件）は NULL にして、生産性・サイクルタイムを出さない
$rawSec = "((TIME_TO_SEC(t_press.press_finish_at) - TIME_TO_SEC(t_press.press_start_at)) - IFNULL(ttp.stop_seconds, 0))";
$netSec = "(CASE WHEN {$rawSec} > 0 THEN {$rawSec} END)";
// 総重量（kg）＝ 切出本数 × 比重(kg/m) × 製品長さ(m)
$weightKg = "(tpq.quantity * IFNULL(mpn.specific_weight, 0) * IFNULL(mpn.production_length, 0))";

$sql = "
SELECT
    t_press.id,
    DATE_FORMAT(t_press.press_date_at, '%y-%m-%d') AS prs_d,
    m_dies.die_number,
    mpn.production_number,
    IF(v.production_number_id IS NULL, 'die', 'variant') AS pn_source,
    m_pressing_type.pressing_type,
    t_press.press_machine_no,
    t_press.plan_billet_quantities,
    t30.prs_quantity,
    t20.work_quantity,
    t10.total_ng,
    t20.work_quantity - t10.total_ng AS total_ok,
    CONCAT(ROUND((t20.work_quantity - t10.total_ng + t10.code_401) / t20.work_quantity * 100, 1), '%') AS per,
    DATE_FORMAT(t_press.dimension_check_date, '%m-%d') AS dcd,
    CASE
        WHEN t_press_sub.etching_check_staff IS NOT NULL AND t_press_sub.etching_finish IN (1, 2)
        THEN DATE_FORMAT(t_press.etching_check_date, '%m-%d')
        WHEN t_press_sub.etching_check_staff IS NULL OR t_press_sub.etching_finish = 0
        THEN ''
    END AS ett,
    DATE_FORMAT(t_press.hardness_check_date, '%m-%d') AS hcd,
    CASE WHEN t_press.qc_check_id IN (1, 2) THEN 'Checked' ELSE '' END AS qc_check_result,
    DATE_FORMAT(t_press.packing_check_date, '%m-%d') AS pcd,
{$ngCols},
    CONCAT(FORMAT(ROUND({$weightKg} / 1000 / NULLIF({$netSec} / 3600, 0), 4), 2), ' ton/h') AS productivity,
    CONCAT(ROUND({$netSec} / NULLIF(tpq.quantity, 0), 0), ' s/pcs') AS actual_cycletime,
    ROUND({$weightKg} / 1000, 2) AS total_weight,
    CASE
        WHEN {$weightKg} IS NULL OR {$weightKg} = 0 THEN NULL
        ELSE ROUND(NULLIF({$netSec} / 3600, 0), 2)
    END AS total_time,
    t_press.special_note
FROM t_press
LEFT JOIN m_pressing_type ON t_press.pressing_type_id = m_pressing_type.id
LEFT JOIN m_dies ON t_press.dies_id = m_dies.id
LEFT JOIN m_die_production_number_variants v ON v.id = t_press.die_production_number_variant_id
LEFT JOIN m_production_numbers mpn ON mpn.id = COALESCE(v.production_number_id, m_dies.production_number_id)
LEFT JOIN t_press_sub ON t_press.id = t_press_sub.press_id
LEFT JOIN (
    SELECT press_id, SUM(work_quantity) AS quantity
    FROM t_press_work_length_quantity
    GROUP BY press_id
) tpq ON tpq.press_id = t_press.id
LEFT JOIN (
    SELECT press_id,
           SUM(CASE WHEN Code <> 700 THEN TIME_TO_SEC(time_end) - TIME_TO_SEC(time_start) ELSE 0 END) AS stop_seconds
    FROM t_time_press
    GROUP BY press_id
) ttp ON ttp.press_id = t_press.id
LEFT JOIN (
    SELECT
        r.t_press_id,
        SUM(q.ng_quantities) AS total_ng,
{$ngSums}
    FROM t_using_aging_rack r
    LEFT JOIN t_press_quality q ON q.using_aging_rack_id = r.id
    LEFT JOIN m_quality_code c  ON q.quality_code_id = c.id
    GROUP BY r.t_press_id
) t10 ON t10.t_press_id = t_press.id
LEFT JOIN (
    SELECT t_press_id, SUM(work_quantity) AS work_quantity
    FROM t_using_aging_rack
    GROUP BY t_press_id
) t20 ON t20.t_press_id = t_press.id
LEFT JOIN (
    SELECT press_id, SUM(quantity) AS prs_quantity
    FROM t_bundle
    GROUP BY press_id
) t30 ON t30.press_id = t_press.id
WHERE {$whereSql}
GROUP BY t_press.id
ORDER BY t_press.press_date_at DESC, t_press.press_start_at DESC
LIMIT 400
";

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
