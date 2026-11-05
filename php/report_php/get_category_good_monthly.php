<?php
// カテゴリ別の良品重量（t）と良品長さ（m）の月別集計（category_good_report.html）
//   良品本数 ＝ 時効ラックの本数の合計 − NG の合計（get_good_weight_monthly.php と同じ）
//   1本の長さ ＝ 実測の長さ first_actual_length（mm）、無ければ品番の製品長 production_length（m）× 1000
//   良品重量（t）＝ 良品本数 × 単重（kg/m）× 1本の長さ（mm）÷ 1,000,000
//   良品長さ（m）＝ 良品本数 × 1本の長さ（mm）÷ 1,000
//   カテゴリは押出した型の品番（m_dies.production_number_id）のカテゴリ2 → カテゴリ1。カテゴリの無い品番は「未分類」
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
    SELECT
        c2.id                                   AS cat2_id,
        c2.name_jp                              AS cat2_jp,
        c2.name_vn                              AS cat2_vn,
        c1.id                                   AS cat1_id,
        c1.name_jp                              AS cat1_jp,
        c1.name_vn                              AS cat1_vn,
        DATE_FORMAT(p.press_date_at, '%Y-%m')   AS month,
        SUM(
            (IFNULL(t20.work_quantity, 0) - IFNULL(t10.total_ng, 0))
            * IFNULL(mpn.specific_weight, 0)
            * COALESCE(p.first_actual_length, mpn.production_length * 1000)
            / 1000000
        )                                        AS weight_t,
        SUM(
            (IFNULL(t20.work_quantity, 0) - IFNULL(t10.total_ng, 0))
            * COALESCE(p.first_actual_length, mpn.production_length * 1000)
            / 1000
        )                                        AS length_m
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
      AND p.billet_size > 0  -- 押出投入実績の良品重量と同じ条件（合計が合うように）
    GROUP BY c2.id, c2.name_jp, c2.name_vn, c1.id, c1.name_jp, c1.name_vn, month
    ORDER BY month
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$groups = [];
foreach ($rows as $r) {
    $mo  = $r["month"];
    $key = (string)($r["cat2_id"] ?? "none");
    $months[$mo] = true;
    if (!isset($groups[$key])) {
        $groups[$key] = [
            "cat1Id" => $r["cat1_id"] === null ? null : (int)$r["cat1_id"],
            "cat1Jp" => $r["cat1_jp"],
            "cat1Vn" => $r["cat1_vn"],
            "cat2Id" => $r["cat2_id"] === null ? null : (int)$r["cat2_id"],
            "cat2Jp" => $r["cat2_jp"],
            "cat2Vn" => $r["cat2_vn"],
            "weight" => [],
            "length" => [],
        ];
    }
    $groups[$key]["weight"][$mo] = round((float)$r["weight_t"], 3);
    $groups[$key]["length"][$mo] = round((float)$r["length_m"], 1);
}

$months = array_keys($months);
sort($months);

echo json_encode(["months" => $months, "rows" => array_values($groups)], JSON_UNESCAPED_UNICODE);
