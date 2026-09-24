<?php
// トップメニュー右上の設備稼働状況（ex0.11 SelPressStatus.php 相当）
// t_plc_web_log から号機ごとの最新1行を返す。
// age_min: 最終記録からの経過分（DBサーバー時刻基準。ブラウザのタイムゾーンに依存しない）
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// 表示する号機（ラベル => t_plc_web_log.machine）
$machines = [
    "No1" => 1,
    "No2" => 2,
];

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("
        SELECT date_time, die_name, press_mode,
               TIMESTAMPDIFF(MINUTE, date_time, NOW()) AS age_min
        FROM t_plc_web_log
        WHERE machine = :machine
        ORDER BY id DESC
        LIMIT 1
    ");

    $result = [];
    foreach ($machines as $label => $machine) {
        $stmt->execute([":machine" => $machine]);
        $row = $stmt->fetch();
        $result[] = [
            "label"      => $label,
            "machine"    => $machine,
            "die_name"   => $row["die_name"]   ?? null,
            "press_mode" => isset($row["press_mode"]) ? (int)$row["press_mode"] : null,
            "date_time"  => $row["date_time"]  ?? null,
            "age_min"    => isset($row["age_min"]) ? (int)$row["age_min"] : null,
        ];
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
