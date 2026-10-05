<?php
// 押出時間の標準（号機1行）の保存
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$b = json_decode(file_get_contents("php://input"), true) ?? [];
$mc = (int)($b["press_machine"] ?? 0);
$staffId = (int)($b["staff_id"] ?? 0);
$vals = [];
foreach (["startup_sec", "billet_change_sec"] as $k) {
    $v = (string)($b[$k] ?? "");
    if (!preg_match('/^\d{1,5}$/', $v)) {
        echo json_encode(["ok" => false, "message" => "秒は 0〜99999 の整数で入れてください"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $vals[$k] = (int)$v;
}
if ($mc < 1 || $mc > 4 || $staffId <= 0) {
    echo json_encode(["ok" => false, "message" => "号機と担当者を選んでください"], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt = $pdo->prepare("
    UPDATE m_press_time_standard
    SET startup_sec = :startup, billet_change_sec = :change, updated_staff_id = :staff, updated_at = NOW()
    WHERE press_machine = :mc
");
$stmt->execute([":startup" => $vals["startup_sec"], ":change" => $vals["billet_change_sec"], ":staff" => $staffId, ":mc" => $mc]);
echo json_encode(["ok" => true]);
