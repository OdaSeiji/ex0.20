<?php
// ダウンロード：Quality Summary Table（ex0.11 CsvDownLoad.html の「Quality Summary Table」を移植、2026-11-24）
//   元：exd11 php/Download/quality-summmary_table__download.php
//   列の並びと見出し（英語）は ex0.11 と同じ（Excel などで読んでいても、そのまま使えるように）。
//   ・aging_check_date は t_press から消したので（2026-11）、同じ意味の hardness_check_date を入れる
//     （ex0.11 の QC 画面でも「Aging Check」は hardness_check_date を表示していた）。
//   ・ex0.11 は CSV だったが、ここでは Excel（.xlsx）にする（2026-11-24 ユーザー）。
//     このファイルは見出しと行を JSON で返すだけ。Excel は download.html が lib/xlsx.js（SheetJS）で作る。
//   ・ex0.11 と同じく、全期間（新しい押出から）。
require_once "../db.php";

$codes = [301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316,
          317, 318, 319, 320, 321, 322, 323, 324, 351, 401];

$header = [
    "press_date_at", "pressing_time", "machine no.", "die_number", "production_number", "specific_weight",
    "production_length", "production_weight", "discard_thickness", "discard_weight",
    "pressing_type", "plan_billet_quantities", "actual_billet_quantities",
    "billet_length", "billet_size",
    "work_quantity", "total_ng", "total_ok", "dimension_check_date", "etching_check_date",
    "aging_check_date", "packing_check_date",
];
foreach ($codes as $c) $header[] = "code_$c";

$codeCols = implode(",\n", array_map(
    fn($c) => "SUM(CASE WHEN m_quality_code.quality_code = $c THEN t_press_quality.ng_quantities ELSE 0 END) AS code_$c",
    $codes
));
$codeSelect = implode(", ", array_map(fn($c) => "t10.code_$c", $codes));

$sql = "
    SELECT
        DATE_FORMAT(t_press.press_date_at, '%Y-%m-%d') AS press_date,
        ROUND(TIMESTAMPDIFF(MINUTE, t_press.press_start_at, t_press.press_finish_at) / 60, 2) AS pressing_time,
        t_press.press_machine_no,
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
        $codeSelect
    FROM t_press
    LEFT JOIN m_pressing_type ON t_press.pressing_type_id = m_pressing_type.id
    LEFT JOIN m_dies ON t_press.dies_id = m_dies.id
    LEFT JOIN m_production_numbers ON m_dies.production_number_id = m_production_numbers.id
    LEFT JOIN t_press_directive ON t_press.press_directive_id = t_press_directive.id
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

// 文字の列（日付・型番・品番・押出種別）以外は数値にして返す（Excel で数として扱えるように）
$textCols = [0, 3, 4, 10, 18, 19, 20, 21];
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
