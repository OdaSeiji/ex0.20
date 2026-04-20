<?php
header("Content-Type: application/json; charset=utf-8");

// ------------------------------------------------------------
// ① DB接続（get_die_list.php と同じ方式）
// ------------------------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=extrusion;charset=utf8",
        "webuser",
        ""
    );
} catch (PDOException $e) {
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 今日と 1 年前
$today = date("Y-m-d");
$one_year_ago = date("Y-m-d", strtotime("-1 year"));

// ------------------------------------------------------------
// ② 修理データ取得（t_die_clinical_record → t_die_issue → m_dies）
// ------------------------------------------------------------
$sql_repair = "
    SELECT 
        d.die_number,
        r.record_date
    FROM t_die_clinical_record r
    JOIN t_die_issue i ON r.issue_id = i.id
    JOIN m_dies d ON i.die_id = d.id
    WHERE r.record_date BETWEEN :one_year_ago AND :today
    ORDER BY d.die_number, r.record_date
";

$stmt = $pdo->prepare($sql_repair);
$stmt->execute([
    ":one_year_ago" => $one_year_ago,
    ":today" => $today
]);

$repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------------------
// ③ 生産データ取得（t_press → m_dies）
// ------------------------------------------------------------
$sql_press = "
    SELECT 
        d.die_number,
        p.press_date_at AS press_date
    FROM t_press p
    JOIN m_dies d ON p.dies_id = d.id
    WHERE p.press_date_at BETWEEN :one_year_ago AND :today
    ORDER BY d.die_number, p.press_date_at
";

$stmt = $pdo->prepare($sql_press);
$stmt->execute([
    ":one_year_ago" => $one_year_ago,
    ":today" => $today
]);

$press = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------------------
// ④ 金型ごとにデータをまとめる
// ------------------------------------------------------------
$result = [];

foreach ($repairs as $r) {
    $die = $r["die_number"];

    if (!isset($result[$die])) {
        $result[$die] = [
            "die_number" => $die,
            "repairs" => [],
            "press" => []
        ];
    }

    $result[$die]["repairs"][] = [
        "date" => $r["record_date"]
    ];
}

foreach ($press as $p) {
    $die = $p["die_number"];

    if (!isset($result[$die])) {
        // 修理が無い金型はここで作らない（後で除外される）
        $result[$die] = [
            "die_number" => $die,
            "repairs" => [],
            "press" => []
        ];
    }

    $result[$die]["press"][] = [
        "date" => $p["press_date"]
    ];
}

// ------------------------------------------------------------
// ⑤ 修理がある金型だけ残す（★ ここが重要）
// ------------------------------------------------------------
$result = array_filter($result, function ($row) {
    return count($row["repairs"]) > 0;
});

// ------------------------------------------------------------
// ⑥ JSON 出力
// ------------------------------------------------------------
echo json_encode(array_values($result), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);