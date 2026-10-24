<?php
require_once __DIR__ . "/common.php";

// エッチング評価記録：押出の一覧（新しい順に最大 SR_LIST_LIMIT 件）
//   GET die = 型番（部分一致, 省略可）, status = all | open | done
//     open = 未完了・エッチング中（etching_finish 0, 4, 行なし）, done = 完了・製品を捨てた・エッチングしない（1, 2, 3）
//   exd11 EtchingV5 の SelSummaryV4.php の移植。ex0.11 と同じく 種別「〇」の押出は出さない

$die    = trim($_GET["die"] ?? "");
$status = in_array($_GET["status"] ?? "", ["open", "done"], true) ? $_GET["status"] : "all";

// t_press_sub は同じ押出に複数行あることがあるので、いちばん古い行の値を使う（ex0.11 の SelFinish と同じ）
$finishSql = "IFNULL((SELECT s.etching_finish FROM t_press_sub s WHERE s.press_id = p.id ORDER BY s.id LIMIT 1), 0)";
$where = ["d.die_number LIKE :die", "IFNULL(p.pressing_type_id, 0) <> 1"];
if ($status === "open") $where[] = "{$finishSql} IN (0, 4)";
if ($status === "done") $where[] = "{$finishSql} IN (1, 2, 3)";
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
            DATE_FORMAT(p.etching_check_date, '%Y-%m-%d') AS check_date,
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

    // 記録の行数と、どこかの回で NG になった位置の数（t_etching）
    $counts = [];
    if ($rows) {
        $ids = implode(",", array_map(fn($r) => (int)$r["id"], $rows));
        $ng = implode(" OR ", array_map(fn($c) => "{$c[0]} = 2", ER_ROUND_COLS));
        foreach ($pdo->query("SELECT press_id, COUNT(*) AS n, SUM({$ng}) AS ng FROM t_etching WHERE press_id IN ({$ids}) GROUP BY press_id") as $c) {
            $counts[(int)$c["press_id"]] = [(int)$c["n"], (int)$c["ng"]];
        }
    }
    $rows = array_map(function ($r) use ($counts) {
        foreach (["id", "machine", "billets", "finish"] as $k) $r[$k] = $r[$k] === null ? null : (int)$r[$k];
        [$r["records"], $r["ng"]] = $counts[$r["id"]] ?? [0, 0];
        return $r;
    }, $rows);

    echo json_encode(["rows" => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    srFail(500, $e->getMessage());
}
