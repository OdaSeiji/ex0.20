<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";  // ← あなたの DB 接続

$pdo = getPDO(); // db.php 内の PDO 取得関数を想定

// ▼ 今日から過去7日を生成
$days = [];
for ($i = 0; $i < 14; $i++) {
    $date = date("Y-m-d", strtotime("-{$i} day"));
    $days[] = $date;
}

$result = [];

foreach ($days as $day) {

    // ▼ その日の押出（〇）を取得
    $sql = "
        SELECT 
            p.id AS press_id,
            d.die_number
        FROM t_press p
        LEFT JOIN m_dies d ON p.dies_id = d.id
        WHERE p.pressing_type_id = '1'
          AND DATE(p.press_date_at) = :day
        ORDER BY d.die_number
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":day" => $day]);
    $pressList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dayData = [];

    foreach ($pressList as $press) {

        // ▼ 測定結果を取得（最新1件）
        $sql2 = "
            SELECT 
                overall_result AS result,
                DATE_FORMAT(created_at, '%H:%i') AS time
            FROM t_die_inspection
            WHERE press_id = :pid
            ORDER BY inspection_date DESC
            LIMIT 1
        ";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([":pid" => $press["press_id"]]);
        $ins = $stmt2->fetch(PDO::FETCH_ASSOC);

        if (!$ins) {
            // inspection が無い → 未
            $status = "pending";
            $time = "-";
        } else {
            // inspection がある → OK/NG
            $status = ($ins["result"] === "OK") ? "ok" : "ng";
            $time = $ins["time"];
        }

        $dayData[] = [
            "die" => $press["die_number"],
            "status" => $status,
            "time" => $time
        ];
    }
    // ★ ここを追加（空の日をスキップ）
    if (count($dayData) === 0) continue;

    $result[$day] = $dayData;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
