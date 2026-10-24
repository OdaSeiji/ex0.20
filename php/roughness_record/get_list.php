<?php
require_once __DIR__ . "/../sample_record/common.php";

// 粗さ測定記録：押出の一覧（新しい順に最大 SR_LIST_LIMIT 件）
//   GET die = 型番（部分一致, 省略可）, status = all | open | done（open = 記録なし, done = t_measurement に記録あり）
//   exd11 MeasurementV2 の SelSummary.php の移植。粗さには硬度のような「完了」の項目が無いので、記録の有無で分ける

$die    = trim($_GET["die"] ?? "");
$status = in_array($_GET["status"] ?? "", ["open", "done"], true) ? $_GET["status"] : "all";

$where = ["d.die_number LIKE :die"];
if ($status === "open") $where[] = "NOT EXISTS (SELECT 1 FROM t_measurement m WHERE m.press_id = p.id)";
if ($status === "done") $where[] = "EXISTS (SELECT 1 FROM t_measurement m WHERE m.press_id = p.id)";
$whereSql = implode(" AND ", $where);

try {
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            DATE_FORMAT(p.press_date_at, '%Y-%m-%d') AS press_date,
            p.press_machine_no                       AS machine,
            d.die_number,
            t.pressing_type,
            p.actual_billet_quantities               AS billets,
            DATE_FORMAT(p.measurement_check_date, '%Y-%m-%d') AS check_date,
            st.staff_name
        FROM t_press p
        LEFT JOIN m_dies d ON d.id = p.dies_id
        LEFT JOIN m_pressing_type t ON t.id = p.pressing_type_id
        LEFT JOIN m_staff st ON st.id = p.measurement_staff
        WHERE {$whereSql}
        ORDER BY p.press_date_at DESC, p.press_start_at DESC, p.id DESC
        LIMIT " . SR_LIST_LIMIT
    );
    $stmt->execute([":die" => "%{$die}%"]);
    $rows = $stmt->fetchAll();

    // 記録の行数（t_measurement）
    $counts = [];
    if ($rows) {
        $ids = implode(",", array_map(fn($r) => (int)$r["id"], $rows));
        foreach ($pdo->query("SELECT press_id, COUNT(*) AS n FROM t_measurement WHERE press_id IN ({$ids}) GROUP BY press_id") as $c) {
            $counts[(int)$c["press_id"]] = (int)$c["n"];
        }
    }
    $rows = array_map(function ($r) use ($counts) {
        foreach (["id", "machine", "billets"] as $k) $r[$k] = $r[$k] === null ? null : (int)$r[$k];
        $r["records"] = $counts[$r["id"]] ?? 0;
        return $r;
    }, $rows);

    echo json_encode(["rows" => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    srFail(500, $e->getMessage());
}
