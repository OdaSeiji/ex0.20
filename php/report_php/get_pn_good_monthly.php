<?php
// 品番別の良品重量（kg）の月別集計（pn_good_report.html）
//   計算は get_category_good_monthly.php（カテゴリ別 良品実績）と同じ。単位だけ kg。
//   良品本数 ＝ 時効ラックの本数の合計 − NG の合計
//   1本の長さ ＝ 実測の長さ first_actual_length（mm）、無ければ品番の製品長 production_length（m）× 1000
//   良品重量（kg）＝ 良品本数 × 単重（kg/m）× 1本の長さ（mm）÷ 1,000
//   品番は押出した型の品番（m_dies.production_number_id）
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
    SELECT
        mpn.id                                  AS pn_id,
        mpn.production_number                   AS pn,
        c2.name_jp                              AS cat2_jp,
        c2.name_vn                              AS cat2_vn,
        c1.name_jp                              AS cat1_jp,
        c1.name_vn                              AS cat1_vn,
        DATE_FORMAT(p.press_date_at, '%Y-%m')   AS month,
        SUM(
            (IFNULL(t20.work_quantity, 0) - IFNULL(t10.total_ng, 0))
            * IFNULL(mpn.specific_weight, 0)
            * COALESCE(p.first_actual_length, mpn.production_length * 1000)
            / 1000
        )                                        AS weight_kg
    FROM t_press p
    JOIN (
        SELECT t_press_id, SUM(work_quantity) AS work_quantity
        FROM t_using_aging_rack
        GROUP BY t_press_id
    ) t20 ON t20.t_press_id = p.id
    LEFT JOIN (
        SELECT
            t_using_aging_rack.t_press_id,
            SUM(t_press_quality.ng_quantities) AS total_ng
        FROM t_using_aging_rack
        LEFT JOIN t_press_quality ON t_press_quality.using_aging_rack_id = t_using_aging_rack.id
        GROUP BY t_using_aging_rack.t_press_id
    ) t10 ON t10.t_press_id = p.id
    LEFT JOIN m_dies mdie ON p.dies_id = mdie.id
    LEFT JOIN m_production_numbers mpn ON mdie.production_number_id = mpn.id
    LEFT JOIN m_production_numbers_category2 c2 ON c2.id = mpn.production_category2_id
    LEFT JOIN m_production_numbers_category1 c1 ON c1.id = c2.category1_id
    WHERE p.press_date_at <> '0000-00-00'
      AND p.billet_size > 0  -- カテゴリ別 良品実績と同じ条件（合計が合うように）
    GROUP BY mpn.id, mpn.production_number, c2.name_jp, c2.name_vn, c1.name_jp, c1.name_vn, month
    ORDER BY month
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$groups = [];
foreach ($rows as $r) {
    $mo  = $r["month"];
    $key = (string)($r["pn_id"] ?? "none");
    $months[$mo] = true;
    if (!isset($groups[$key])) {
        $groups[$key] = [
            "pn"     => $r["pn"],
            "cat1Jp" => $r["cat1_jp"],
            "cat1Vn" => $r["cat1_vn"],
            "cat2Jp" => $r["cat2_jp"],
            "cat2Vn" => $r["cat2_vn"],
            "weight" => [],
        ];
    }
    $groups[$key]["weight"][$mo] = round((float)$r["weight_kg"], 1);
}

$months = array_keys($months);
sort($months);

echo json_encode(["months" => $months, "rows" => array_values($groups)], JSON_UNESCAPED_UNICODE);
