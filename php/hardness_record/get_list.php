<?php
require_once __DIR__ . "/../sample_record/common.php";

// 硬さ測定記録：押出の一覧（新しい順に最大 SR_LIST_LIMIT 件）
//   GET die = 型番（部分一致, 省略可）, status = all | open | done（完了 = t_press_sub.hardness_finish = 1）
//   exd11 HardNessV3 の SelSummary.php の移植。記録の件数・完了は、表示する押出の分だけ集計する

$die    = trim($_GET["die"] ?? "");
$status = in_array($_GET["status"] ?? "", ["open", "done"], true) ? $_GET["status"] : "all";

$finishSql = "IFNULL((SELECT MAX(s.hardness_finish) FROM t_press_sub s WHERE s.press_id = p.id), 0)";
$where = ["d.die_number LIKE :die"];
if ($status === "open") $where[] = "{$finishSql} <> 1";
if ($status === "done") $where[] = "{$finishSql} = 1";
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
            DATE_FORMAT(p.hardness_check_date, '%Y-%m-%d') AS check_date,
            {$finishSql}                             AS finish
        FROM t_press p
        LEFT JOIN m_dies d ON d.id = p.dies_id
        LEFT JOIN m_pressing_type t ON t.id = p.pressing_type_id
        WHERE {$whereSql}
        ORDER BY p.press_date_at DESC, p.press_start_at DESC, p.id DESC
        LIMIT " . SR_LIST_LIMIT
    );
    $stmt->execute([":die" => "%{$die}%"]);
    $rows = $stmt->fetchAll();

    // 記録の行数（t_hardness）
    $counts = [];
    if ($rows) {
        $ids = implode(",", array_map(fn($r) => (int)$r["id"], $rows));
        foreach ($pdo->query("SELECT press_id, COUNT(*) AS n FROM t_hardness WHERE press_id IN ({$ids}) GROUP BY press_id") as $c) {
            $counts[(int)$c["press_id"]] = (int)$c["n"];
        }
    }
    $rows = array_map(function ($r) use ($counts) {
        foreach (["id", "machine", "billets", "finish"] as $k) $r[$k] = $r[$k] === null ? null : (int)$r[$k];
        $r["records"] = $counts[$r["id"]] ?? 0;
        return $r;
    }, $rows);

    echo json_encode(["rows" => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    srFail(500, $e->getMessage());
}
