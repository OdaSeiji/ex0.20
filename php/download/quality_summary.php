<?php
// ダウンロード：Quality Summary Table（ex0.11 CsvDownLoad.html の「Quality Summary Table」を移植、2026-11-24）
//   元：exd11 php/Download/quality-summmary_table__download.php
//   列の並びと見出し（英語）は ex0.11 と同じ（Excel などで読んでいても、そのまま使えるように）。
//   ・aging_check_date は t_press から消したので（2026-11）、同じ意味の hardness_check_date を入れる
//     （ex0.11 の QC 画面でも「Aging Check」は hardness_check_date を表示していた）。
//   ・ex0.11 は CSV だったが、ここでは Excel（.xlsx）にする（2026-11-24 ユーザー）。
//     このファイルは見出しと行を JSON で返すだけ。Excel は download.html が lib/xlsx.js（SheetJS）で作る。
//   ・ex0.11 と同じく、全期間（新しい押出から）。
//   ・2026-11-25 ユーザー：列の並びを旧画面（ex0.11 quality-summmary_table__download3.php ＋ ordersheet_number）に合わせる。
//     machine は code_401 の後ろ。その後ろに mfg・productivity・total_time・time code 300・time code 200・ordersheet_number。
//     productivity・total_time は品質サマリー画面（php/quality_summary/get_summary.php）と同じ計算
//     （品番バリアントの比重・製品長さ、正味押出時間が0以下なら空欄）。Excel で計算できるように単位の文字は付けない。
//     mfg は束（t_bundle）の製造（1 Dubai／2 VN）。1回の押出に両方あるときは「Dubai/VN」。
require_once "../db.php";

$codes = [301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316,
          317, 318, 319, 320, 321, 322, 323, 324, 351, 401];

$header = [
    "press_date_at", "pressing_time", "die_number", "production_number", "specific_weight",
    "production_length", "production_weight", "discard_thickness", "discard_weight",
    "pressing_type", "plan_billet_quantities", "actual_billet_quantities",
    "billet_length", "billet_size",
    "work_quantity", "total_ng", "total_ok", "dimension_check_date", "etching_check_date",
    "aging_check_date", "packing_check_date",
];
foreach ($codes as $c) $header[] = "code_$c";
array_push($header, "machine", "mfg", "productivity", "total_time", "time code 300", "time code 200", "ordersheet_number");

// 正味押出時間（秒）・総重量（kg）：品質サマリー画面（get_summary.php）と同じ
$rawSec   = "((TIME_TO_SEC(t_press.press_finish_at) - TIME_TO_SEC(t_press.press_start_at)) - IFNULL(ttp.stop_seconds, 0))";
$netSec   = "(CASE WHEN {$rawSec} > 0 THEN {$rawSec} END)";
$weightKg = "(tpq.quantity * IFNULL(mpn.specific_weight, 0) * IFNULL(mpn.production_length, 0))";

$codeCols = implode(",\n", array_map(
    fn($c) => "SUM(CASE WHEN m_quality_code.quality_code = $c THEN t_press_quality.ng_quantities ELSE 0 END) AS code_$c",
    $codes
));
$codeSelect = implode(", ", array_map(fn($c) => "t10.code_$c", $codes));

$sql = "
    SELECT
        DATE_FORMAT(t_press.press_date_at, '%Y-%m-%d') AS press_date,
        ROUND(TIMESTAMPDIFF(MINUTE, t_press.press_start_at, t_press.press_finish_at) / 60, 2) AS pressing_time,
        m_dies.die_number,
        m_production_numbers.production_number,
        m_production_numbers.specific_weight,
        m_production_numbers.production_length,
        ROUND(m_production_numbers.specific_weight * m_production_numbers.production_length, 3) AS work_weight,
        t_press_directive.discard_thickness,
        ROUND(t_press_directive.discard_thickness * 237 * 237 / 4 * 3.14 * 2.7 / 1000 / 1000, 2) AS discard_weight,
        m_pressing_type.pressing_type,
        t_press.plan_billet_quantities,
        t_press.actual_billet_quantities,
        t_press.billet_length,
        t_press.billet_size,
        t20.work_quantity,
        t10.total_ng,
        t20.work_quantity - t10.total_ng AS total_ok,
        t_press.dimension_check_date,
        t_press.etching_check_date,
        t_press.hardness_check_date,
        t_press.packing_check_date,
        $codeSelect,
        t_press.press_machine_no,
        tb.mfg,
        ROUND({$weightKg} / 1000 / NULLIF({$netSec} / 3600, 0), 2) AS productivity,
        CASE WHEN {$weightKg} IS NULL OR {$weightKg} = 0 THEN NULL
             ELSE ROUND(NULLIF({$netSec} / 3600, 0), 2) END AS total_time,
        ROUND(IFNULL(ttp.time_300, 0), 4) AS time_code_300,
        ROUND(IFNULL(ttp.time_200, 0), 4) AS time_code_200,
        m_ordersheet.ordersheet_number
    FROM t_press
    LEFT JOIN m_pressing_type ON t_press.pressing_type_id = m_pressing_type.id
    LEFT JOIN m_dies ON t_press.dies_id = m_dies.id
    LEFT JOIN m_production_numbers ON m_dies.production_number_id = m_production_numbers.id
    LEFT JOIN t_press_directive ON t_press.press_directive_id = t_press_directive.id
    LEFT JOIN m_die_production_number_variants v ON v.id = t_press.die_production_number_variant_id
    LEFT JOIN m_production_numbers mpn ON mpn.id = COALESCE(v.production_number_id, m_dies.production_number_id)
    LEFT JOIN m_ordersheet ON m_ordersheet.id = t_press.ordersheet_id
    LEFT JOIN (
        SELECT press_id,
               GROUP_CONCAT(DISTINCT CASE mfg WHEN 1 THEN 'Dubai' WHEN 2 THEN 'VN' END ORDER BY mfg SEPARATOR '/') AS mfg
        FROM t_bundle
        GROUP BY press_id
    ) tb ON tb.press_id = t_press.id
    LEFT JOIN (
        SELECT press_id, SUM(work_quantity) AS quantity
        FROM t_press_work_length_quantity
        GROUP BY press_id
    ) tpq ON tpq.press_id = t_press.id
    LEFT JOIN (
        SELECT press_id,
               SUM(CASE WHEN Code <> 700 THEN TIME_TO_SEC(time_end) - TIME_TO_SEC(time_start) ELSE 0 END) AS stop_seconds,
               SUM(CASE WHEN Code = 300 THEN (TIME_TO_SEC(time_end) - TIME_TO_SEC(time_start)) / 3600 ELSE 0 END) AS time_300,
               SUM(CASE WHEN Code = 200 THEN (TIME_TO_SEC(time_end) - TIME_TO_SEC(time_start)) / 3600 ELSE 0 END) AS time_200
        FROM t_time_press
        GROUP BY press_id
    ) ttp ON ttp.press_id = t_press.id
    LEFT JOIN (
        SELECT
            t_using_aging_rack.t_press_id AS t_press_id,
            SUM(t_press_quality.ng_quantities) AS total_ng,
            $codeCols
        FROM t_using_aging_rack
        LEFT JOIN t_press_quality ON t_press_quality.using_aging_rack_id = t_using_aging_rack.id
        LEFT JOIN m_quality_code ON t_press_quality.quality_code_id = m_quality_code.id
        GROUP BY t_using_aging_rack.t_press_id
    ) t10 ON t10.t_press_id = t_press.id
    LEFT JOIN (
        SELECT t_using_aging_rack.t_press_id, SUM(t_using_aging_rack.work_quantity) AS work_quantity
        FROM t_using_aging_rack
        GROUP BY t_using_aging_rack.t_press_id
    ) t20 ON t20.t_press_id = t_press.id
    ORDER BY t_press.press_date_at DESC, t_press.press_start_at
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);

// 文字の列（日付・型番・品番・押出種別・mfg・オーダーシート番号）以外は数値にして返す（Excel で数として扱えるように）
$textCols = array_keys(array_intersect($header, [
    "press_date_at", "die_number", "production_number", "pressing_type",
    "dimension_check_date", "etching_check_date", "aging_check_date", "packing_check_date",
    "mfg", "ordersheet_number",
]));
foreach ($rows as &$r) {
    foreach ($r as $i => $v) {
        if ($v !== null && !in_array($i, $textCols, true) && is_numeric($v)) $r[$i] = $v + 0;
    }
}
unset($r);

header("Content-Type: application/json; charset=UTF-8");
echo json_encode([
    "sheet"  => "Quality Summary",
    "file"   => "quality-summary_" . date("Y-m-d"),
    "header" => $header,
    "rows"   => $rows,
], JSON_UNESCAPED_UNICODE);
