<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$input  = json_decode(file_get_contents("php://input"), true);
$action = $input["action"] ?? null;
$id     = $input["id"]     ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID required"]);
    exit;
}

if ($action === "update_staff") {
    // 社員番号・氏名・ロール・在籍状況（退職日。空なら在籍）をまとめて保存する。パスワードはここでは変えない
    $num   = trim((string)($input["emploee_number"] ?? ""));
    $name  = trim(preg_replace('/\s+/u', " ", (string)($input["staff_name"] ?? "")));
    $role  = $input["role"] ?? null;
    $leave = $input["leave_at"] ?? null;
    $allowed = ["admin", "operator", "maintenance", "inspector", "die_setup", "die_engineer"];
    $fail = function (string $msg) {
        echo json_encode(["status" => "error", "message" => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    };

    // 日本人スタッフの6桁の番号などがあるので、編集では「数字だけ」を確かめる（新規登録は7桁）
    if (!preg_match('/^\d{1,9}$/', $num)) $fail("社員番号は数字で入れてください");
    if (mb_strlen($name) < 2 || mb_strlen($name) > 40) $fail("氏名は2〜40文字で入れてください");
    if ($role === "") $role = null;
    if ($role !== null && !in_array($role, $allowed, true)) $fail("ロールが不正です");
    if ($leave === "") $leave = null;
    if ($leave !== null && (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $leave, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1]))) $fail("退職日が不正です");

    $chk = $pdo->prepare("SELECT staff_name FROM m_staff WHERE emploee_number = ? AND id <> ?");
    $chk->execute([(int)$num, $id]);
    if (($dup = $chk->fetchColumn()) !== false) $fail("社員番号 {$num} は {$dup} さんが使っています");

    $stmt = $pdo->prepare("UPDATE m_staff SET emploee_number = ?, staff_name = ?, role = ?, leave_at = ? WHERE id = ?");
    $stmt->execute([(int)$num, $name, $role, $leave, $id]);
    echo json_encode(["status" => "ok"]);

} elseif ($action === "update_role") {
    $role    = $input["role"] ?? null;
    $allowed = ["admin", "operator", "maintenance", "inspector", "die_setup", "die_engineer"];

    if ($role !== null && !in_array($role, $allowed)) {
        echo json_encode(["status" => "error", "message" => "invalid role"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE m_staff SET role = ? WHERE id = ?");
    $stmt->execute([$role, $id]);
    echo json_encode(["status" => "ok"]);

} elseif ($action === "reset_password") {
    $emploee_number = $input["emploee_number"] ?? "0000";
    $hashed = password_hash((string)$emploee_number, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE m_staff SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $id]);
    echo json_encode(["status" => "ok"]);

} else {
    echo json_encode(["status" => "error", "message" => "unknown action"]);
}
