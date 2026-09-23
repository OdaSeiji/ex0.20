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

    } elseif ($mode === "summary") {
        // 指定日・型番の各ビレットについて、ラム位置1000/400/200mm時点の値（最近傍点）をまとめて返す
        $date = $_GET["date"] ?? "";
        $dieName = $_GET["die_name"] ?? "";
        $stmt = $pdo->prepare("
            SELECT billet_counter, ram_position, ram_speed, ram_pressure, exit_temp
            FROM t_press_ram_log
            WHERE DATE(date_time) = :date
              AND die_name = :die_name
            ORDER BY billet_counter, date_time
        ");
        $stmt->execute([":date" => $date, ":die_name" => $dieName]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $targets = [1000, 400, 200];
        $byBillet = [];
        foreach ($rows as $r) {
            $byBillet[$r["billet_counter"]][] = $r;
        }

        $result = [];
        foreach ($byBillet as $billetCounter => $billetRows) {
            $entry = ["billet_counter" => $billetCounter];
            foreach ($targets as $t) {
                $nearest = null;
                $bestDiff = null;
                foreach ($billetRows as $r) {
                    $diff = abs($r["ram_position"] - $t);
                    if ($bestDiff === null || $diff < $bestDiff) {
                        $bestDiff = $diff;
                        $nearest = $r;
                    }
                }
                $entry["pos_" . $t] = [
                    "position" => (int)$nearest["ram_position"],
                    "speed"    => round($nearest["ram_speed"] / 10, 1),
                    "pressure" => round($nearest["ram_pressure"] / 10, 1),
                    "temp"     => (int)$nearest["exit_temp"],
                ];
            }
            $result[] = $entry;
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode(["error" => "invalid mode"]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
