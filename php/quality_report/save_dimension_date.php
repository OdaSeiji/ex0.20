<?php
require_once __DIR__ . "/common.php";

// 寸法の完了日（t_press.dimension_check_date）を保存・取消する
//   POST JSON: { press_id, date: "YYYY-MM-DD" | null }
//   エッチング・時効の完了日は、それぞれエッチング・硬度の画面で入れる（ここでは扱わない）

$body    = qrJsonBody();
$pressId = (int)($body["press_id"] ?? 0);
$date    = $body["date"] ?? null;

if ($date !== null && !qrIsDate($date)) qrFail(400, "日付が不正です");

try {
    if (!qrPressInWindow($pdo, $pressId)) qrFail(409, "1か月より前の押出は、旧画面で修正してください");
    $stmt = $pdo->prepare("UPDATE t_press SET dimension_check_date = :date WHERE id = :id");
    $stmt->bindValue(":date", $date, $date === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(":id", $pressId, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    qrFail(500, $e->getMessage());
}
