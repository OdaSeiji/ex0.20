<?php
// 生産計画：金型一覧（計画を立てるための情報つき）。exd11 PressPlanV12 の SelProductionNumberV4 を移植。
// - 品番は型番-品番の多対多（m_die_production_number_variants）から全部出す
// - 押出長さ(km) などの計算に使う単重は、ex0.11 と同じく m_dies.production_number_id の品番のもの
// - 「洗浄後の押出回数」は、最後の洗浄（Washing / On rack）のあとの押出を数える
//   （ex0.11 は最後の量産1回分しか見ておらず 0/1 にしかならなかった）
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$q = trim((string)($_GET["q"] ?? ""));

$sql = "
WITH
km AS (
    -- 押出1回ごとの押出長さ(km)
    SELECT p.id, p.dies_id, p.press_date_at,
           (PI() * POW(p.billet_size * 25.4 / 2, 2) * p.billet_length * 0.001 * 2.70 * p.actual_billet_quantities / 1000)
             / pn.specific_weight / 1000 / d.hole AS len_km
    FROM t_press p
    JOIN m_dies d ON d.id = p.dies_id
    LEFT JOIN m_production_numbers pn ON pn.id = d.production_number_id
),
last_nit AS (
    SELECT d.id AS dies_id, IFNULL(MAX(n.nitriding_date_at), DATE('2021-01-01')) AS nit_at
    FROM m_dies d LEFT JOIN t_nitriding n ON n.dies_id = d.id
    GROUP BY d.id
),
nit_len AS (
    SELECT km.dies_id, SUM(km.len_km) AS len_km
    FROM km JOIN last_nit ln ON ln.dies_id = km.dies_id
    WHERE km.press_date_at > ln.nit_at
    GROUP BY km.dies_id
),
total_len AS (
    SELECT dies_id, SUM(len_km) AS len_km FROM km GROUP BY dies_id
),
wash_after_nit AS (
    SELECT s.dies_id, COUNT(*) AS cnt
    FROM t_dies_status s JOIN last_nit ln ON ln.dies_id = s.dies_id
    WHERE s.die_status_id = 4 AND s.do_sth_at > ln.nit_at
    GROUP BY s.dies_id
),
last_status AS (
    SELECT * FROM (
        SELECT s.dies_id, s.die_status_id, s.do_sth_at, s.tank,
               ROW_NUMBER() OVER (PARTITION BY s.dies_id ORDER BY s.do_sth_at DESC, s.id DESC) AS rn
        FROM t_dies_status s
    ) x WHERE rn = 1
),
last_wash AS (
    SELECT dies_id, MAX(do_sth_at) AS wash_at
    FROM t_dies_status WHERE die_status_id IN (4, 10)
    GROUP BY dies_id
),
press_after_wash AS (
    SELECT p.dies_id, COUNT(*) AS cnt
    FROM t_press p
    LEFT JOIN last_wash lw ON lw.dies_id = p.dies_id
    WHERE TIMESTAMP(p.press_date_at, IFNULL(p.press_start_at, '00:00:00')) > IFNULL(lw.wash_at, '2000-01-01')
    GROUP BY p.dies_id
),
last_mp AS (
    -- 最後の量産（プレス種別 3）
    SELECT dies_id, MAX(id) AS press_id FROM t_press WHERE pressing_type_id = 3 GROUP BY dies_id
),
mp_rate AS (
    SELECT r.t_press_id,
           SUM(r.work_quantity) AS work_qty,
           SUM(IFNULL(ng.total_ng, 0)) AS total_ng,
           SUM(IFNULL(ng.code_401, 0)) AS code_401
    FROM last_mp lm
    JOIN t_using_aging_rack r ON r.t_press_id = lm.press_id
    LEFT JOIN (
        SELECT q.using_aging_rack_id,
               SUM(q.ng_quantities) AS total_ng,
               SUM(CASE WHEN c.quality_code = 401 THEN q.ng_quantities ELSE 0 END) AS code_401
        FROM t_press_quality q LEFT JOIN m_quality_code c ON c.id = q.quality_code_id
        GROUP BY q.using_aging_rack_id
    ) ng ON ng.using_aging_rack_id = r.id
    GROUP BY r.t_press_id
),
products AS (
    SELECT v.die_id,
           GROUP_CONCAT(CONCAT(pn.id, ':', pn.production_number) ORDER BY pn.production_number SEPARATOR '|') AS products
    FROM m_die_production_number_variants v
    JOIN m_production_numbers pn ON pn.id = v.production_number_id
    GROUP BY v.die_id
)
SELECT
    d.id AS dies_id,
    d.die_number,
    pr.products,
    ls.die_status_id,
    TRIM(CONCAT(IFNULL(st.die_status, ''), ' ', IFNULL(ls.tank, ''))) AS die_status,
    st.die_status AS status_name,
    CASE WHEN ls.die_status_id = 4 AND ls.tank <> '' AND ls.tank <> '0' THEN ls.tank END AS tank,   -- 洗浄タンクの番号（Washing のときだけ）
    DATE_FORMAT(ls.do_sth_at, '%Y-%m-%d %H:%i') AS status_at,
    IFNULL(paw.cnt, 0) AS press_after_wash,
    ROUND(IFNULL(nl.len_km, 0), 2) AS nit_len_km,
    IFNULL(wan.cnt, 0) AS wash_after_nit,
    dia.die_diamater,
    ROUND(IFNULL(tl.len_km, 0), 1) AS total_len_km,
    CASE WHEN mr.work_qty > 0
         THEN ROUND((mr.work_qty - mr.total_ng + mr.code_401) / mr.work_qty * 100, 1) END AS ok_rate,
    -- 次の窒化までに押せるビレット本数（最後の量産のビレットで計算）
    CASE WHEN mp.id IS NULL OR pn.specific_weight IS NULL THEN NULL
         ELSE GREATEST(FLOOR(
            ((CASE WHEN dia.die_diamater >= 300 THEN 2.5 ELSE 3 END) - IFNULL(nl.len_km, 0)) * 1000 * pn.specific_weight * d.hole
            / (PI() * POW(mp.billet_size * 25.4 / 2, 2) * mp.billet_length * 0.001 * 2.70 / 1000)
         ), 0) END AS max_billet,
    mp.billet_length AS mp_billet_length,
    mp.billet_size   AS mp_billet_size
FROM m_dies d
LEFT JOIN products pr        ON pr.die_id = d.id
LEFT JOIN last_status ls     ON ls.dies_id = d.id
LEFT JOIN m_die_status st    ON st.id = ls.die_status_id
LEFT JOIN press_after_wash paw ON paw.dies_id = d.id
LEFT JOIN nit_len nl         ON nl.dies_id = d.id
LEFT JOIN wash_after_nit wan ON wan.dies_id = d.id
LEFT JOIN total_len tl       ON tl.dies_id = d.id
LEFT JOIN m_dies_diamater dia ON dia.id = d.die_diamater_id
LEFT JOIN m_production_numbers pn ON pn.id = d.production_number_id
LEFT JOIN last_mp lm         ON lm.dies_id = d.id
LEFT JOIN t_press mp         ON mp.id = lm.press_id
LEFT JOIN mp_rate mr         ON mr.t_press_id = lm.press_id
WHERE IFNULL(d.is_disable, 0) = 0
  AND (:q1 = '' OR d.die_number LIKE :q2 OR pr.products LIKE :q3)
ORDER BY
    CASE ls.die_status_id
        WHEN 7 THEN 9 WHEN 9 THEN 8 WHEN 3 THEN 7 WHEN 31 THEN 6 WHEN 32 THEN 5
        WHEN 4 THEN 4 WHEN 2 THEN 3 WHEN 1 THEN 2 WHEN 10 THEN 1 ELSE 0
    END DESC,
    d.die_number
";
$stmt = $pdo->prepare($sql);
$like = "%{$q}%";
$stmt->execute([":q1" => $q, ":q2" => $like, ":q3" => $like]);
$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
    $list = [];
    foreach (array_filter(explode("|", (string)$r["products"])) as $p) {
        [$id, $name] = explode(":", $p, 2);
        $list[] = ["id" => (int)$id, "production_number" => $name];
    }
    $r["products"] = $list;
}
unset($r);
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
