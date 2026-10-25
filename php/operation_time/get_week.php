<?php
// 持ち時間の入力：1週間（月〜日）× 号機 1〜3 の設定
//   GET from = YYYY-MM-DD（その日を含む週の月曜から7日）
//   設定は押出スケジュール表と同じ t_press_schedule_day（開始・終了・昼休み）。
//   その週に1行も無い号機は、前の週の値を引き継いで返す（inherited = true。DB にはまだ書いていない）
//   参考に、押出計画（t_press_plan）と日報（t_press）がその日・号機にあるかも返す
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/common.php";

$from = (string)($_GET["from"] ?? "");
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !strtotime($from)) {
    http_response_code(400);
    echo json_encode(["error" => "from が不正です"], JSON_UNESCAPED_UNICODE);
    exit;
}
$monday = otMonday($from);
$to = date("Y-m-d", strtotime("$monday +6 day"));
const OT_MACHINES = [1, 2, 3];

try {
    $days = [];
    $weeks = [];
    foreach (OT_MACHINES as $m) $weeks[$m] = otResolveWeek($pdo, $monday, $m);
    for ($i = 0; $i < 7; $i++) {
        $d = date("Y-m-d", strtotime("$monday +$i day"));
        foreach (OT_MACHINES as $m) {
            $s = $weeks[$m][$i];
            $days[$d][$m] = [
                "start" => $s["start"] ?? null, "end" => $s["end"] ?? null, "has_lunch" => $s["has_lunch"] ?? 0,
                "has_row" => $s !== null && !$s["inherited"], "inherited" => $s !== null && $s["inherited"], "from" => $s["from"] ?? null,
                "plans" => 0, "presses" => 0,
            ];
        }
    }

    $st = $pdo->prepare("SELECT plan_date AS d, press_machine AS m, COUNT(*) AS n FROM t_press_plan WHERE plan_date BETWEEN :f AND :t GROUP BY d, m");
    $st->execute([":f" => $monday, ":t" => $to]);
    foreach ($st as $r) if (isset($days[$r["d"]][(int)$r["m"]])) $days[$r["d"]][(int)$r["m"]]["plans"] = (int)$r["n"];

    $st = $pdo->prepare("SELECT DATE(press_date_at) AS d, press_machine_no AS m, COUNT(*) AS n FROM t_press WHERE press_date_at BETWEEN :f AND :t2 GROUP BY d, m");
    $st->execute([":f" => $monday, ":t2" => "$to 23:59:59"]);
    foreach ($st as $r) if (isset($days[$r["d"]][(int)$r["m"]])) $days[$r["d"]][(int)$r["m"]]["presses"] = (int)$r["n"];

    $out = [];
    foreach ($days as $d => $ms) $out[] = ["date" => $d, "machines" => $ms];
    echo json_encode(["monday" => $monday, "days" => $out], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
