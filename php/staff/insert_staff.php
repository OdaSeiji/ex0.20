<?php
// 新規社員の登録（exd11 StaffAndCode2 の InsMember.php の移植）
//   POST JSON: { emploee_number, staff_name, position_id: 1 = Staff | 2 = Worker, joining_date: "YYYY-MM-DD", role: "" | m_staff.role }
//   退職日は登録しない（退職はあとで設定する）。パスワードは空のまま（スタッフ設定の「リセット」で社員番号になる）
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

function staffFail(string $msg): void {
    echo json_encode(["status" => "error", "message" => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$in   = json_decode(file_get_contents("php://input"), true) ?? [];
$num  = trim((string)($in["emploee_number"] ?? ""));
$name = trim(preg_replace('/\s+/u', " ", (string)($in["staff_name"] ?? "")));
$pos  = (int)($in["position_id"] ?? 0);
$join = (string)($in["joining_date"] ?? "");
$role = (string)($in["role"] ?? "");

if (!preg_match('/^\d{7}$/', $num)) staffFail("社員番号は数字7桁で入れてください");
if (mb_strlen($name) < 2 || mb_strlen($name) > 40) staffFail("氏名は2〜40文字で入れてください");
if (!in_array($pos, [1, 2], true)) staffFail("区分を選んでください");
if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $join, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) staffFail("入社日が不正です");
$roles = ["admin", "operator", "maintenance", "inspector", "die_setup", "die_engineer"];
if ($role !== "" && !in_array($role, $roles, true)) staffFail("ロールが不正です");

try {
    $chk = $pdo->prepare("SELECT staff_name FROM m_staff WHERE emploee_number = ?");
    $chk->execute([(int)$num]);
    if (($dup = $chk->fetchColumn()) !== false) staffFail("社員番号 {$num} はもう登録されています（{$dup}）");

    $stmt = $pdo->prepare("
        INSERT INTO m_staff (emploee_number, staff_name, joining_date, position_id, role, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([(int)$num, $name, $join, $pos, $role === "" ? null : $role]);
    echo json_encode(["status" => "ok", "id" => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    staffFail($e->getMessage());
}
