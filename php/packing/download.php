<?php
require_once __DIR__ . "/common.php";

// 梱包入力：梱包作業の CSV（exd11 php/Packing/DownloadSummary.php の移植。列と並びは同じ）
//   GET from, to = 梱包日の期間（両方あるときだけ絞る）
//   同じ作業（日付・開始・終了・品番）の2行目からは、日付〜品番を空にする（ex0.11 と同じ）

$from = $_GET["from"] ?? "";
$to   = $_GET["to"] ?? "";
$useTerm = pkIsDate($from) && pkIsDate($to);

try {
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(k.packing_date, '%Y-%m-%d') AS d,
            TIME_FORMAT(k.packing_start, '%H:%i')   AS s,
            TIME_FORMAT(k.packing_end, '%H:%i')     AS e,
            o.production_numbers_id                 AS pn_id,
            pn.production_number                    AS pn,
            SUM(b.work_quantity)                    AS qty,
            LEFT(bn.box_number, 10)                 AS order_no,
            (SELECT COUNT(DISTINCT tb.box_number_id)
               FROM t_packing_box tb
               JOIN t_packing_box_number tbn ON tb.box_number_id = tbn.id
              WHERE tb.packing_id = k.id AND LEFT(tbn.box_number, 10) = LEFT(bn.box_number, 10)) AS box_count
        FROM t_packing k
        LEFT JOIN m_ordersheet o ON k.m_ordersheet_id = o.id
        LEFT JOIN m_production_numbers pn ON o.production_numbers_id = pn.id
        LEFT JOIN t_packing_box b ON b.packing_id = k.id
        LEFT JOIN t_packing_box_number bn ON b.box_number_id = bn.id
        " . ($useTerm ? "WHERE k.packing_date BETWEEN :from AND :to" : "") . "
        GROUP BY d, s, e, pn_id, pn, order_no, k.id
        ORDER BY k.packing_date, k.packing_start, order_no
    ");
    $stmt->execute($useTerm ? [":from" => $from, ":to" => $to] : []);
} catch (PDOException $e) {
    pkFail(500, $e->getMessage());
}

$name = $useTerm ? "packing_{$from}_{$to}.csv" : "packing.csv";
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"{$name}\"");
$out = fopen("php://output", "w");
fwrite($out, "\xEF\xBB\xBF");   // Excel 用の BOM
fputcsv($out, ["Date", "Start", "End", "Production ID", "Production Number", "Quantity", "Ordersheet Number", "Total Box"]);
$prev = null;
while ($r = $stmt->fetch()) {
    $key = [$r["d"], $r["s"], $r["e"], $r["pn_id"]];
    $head = $key === $prev ? ["", "", "", "", ""] : [$r["d"], $r["s"], $r["e"], $r["pn_id"], $r["pn"]];
    fputcsv($out, array_merge($head, [$r["qty"], $r["order_no"], $r["box_count"]]));
    $prev = $key;
}
fclose($out);
