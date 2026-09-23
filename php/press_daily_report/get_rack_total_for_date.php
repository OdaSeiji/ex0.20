<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$date = $_GET["date"] ?? "";
if ($date === "") {
    echo json_encode(["total" => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

$excludePressId = isset($_GET["exclude_press_id"]) && $_GET["exclude_press_id"] !== ""
    ? (int)$_GET["exclude_press_id"]
    : null;

// 使用ラック数 = レコードごとの order_number の最大値（そのレコードで使ったラックの本数）。
// それを日付内の全レコードで合計する。
$sql = "
    SELECT COALESCE(SUM(sub.max_order), 0) AS total
    FROM (
        SELECT r.t_press_id, MAX(r.order_number) AS max_order
        FROM t_using_aging_rack r
        INNER JOIN t_press p ON p.id = r.t_press_id
        WHERE DATE(p.press_date_at) = :date
";
$params = [":date" => $date];
if ($excludePressId !== null) {
    $sql .= "          AND p.id != :exclude_press_id\n";
    $params[":exclude_press_id"] = $excludePressId;
}
$sql .= "
        GROUP BY r.t_press_id
    ) sub
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(["total" => (int)$row["total"]], JSON_UNESCAPED_UNICODE);
