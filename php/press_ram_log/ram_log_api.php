<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$pdo = getPDO();
$mode = $_GET["mode"] ?? "";

try {
    if ($mode === "dates") {
        // 日付一覧
        $stmt = $pdo->query("
            SELECT DISTINCT DATE(date_time) AS d
            FROM t_press_ram_log
            ORDER BY d DESC
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_UNESCAPED_UNICODE);

    } elseif ($mode === "dies") {
        // 指定日の型番一覧（号機付き）
        $date = $_GET["date"] ?? "";
        $stmt = $pdo->prepare("
            SELECT DISTINCT die_name, machine
            FROM t_press_ram_log
            WHERE DATE(date_time) = :date
            ORDER BY die_name
        ");
        $stmt->execute([":date" => $date]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

    } elseif ($mode === "billets") {
        // 指定日・型番のビレット本数一覧
        $date = $_GET["date"] ?? "";
        $dieName = $_GET["die_name"] ?? "";
        $stmt = $pdo->prepare("
            SELECT DISTINCT billet_counter
            FROM t_press_ram_log
            WHERE DATE(date_time) = :date
              AND die_name = :die_name
            ORDER BY billet_counter
        ");
        $stmt->execute([":date" => $date, ":die_name" => $dieName]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_UNESCAPED_UNICODE);

    } elseif ($mode === "log") {
        // 指定日・型番・ビレット本数のラムログ本体
        $date = $_GET["date"] ?? "";
        $dieName = $_GET["die_name"] ?? "";
        $billetCounter = $_GET["billet_counter"] ?? "";
        $stmt = $pdo->prepare("
            SELECT ram_position, ram_speed, ram_pressure, exit_temp, date_time
            FROM t_press_ram_log
            WHERE DATE(date_time) = :date
              AND die_name = :die_name
              AND billet_counter = :billet_counter
            ORDER BY date_time
        ");
        $stmt->execute([
            ":date" => $date,
            ":die_name" => $dieName,
            ":billet_counter" => $billetCounter
        ]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode(["error" => "invalid mode"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
