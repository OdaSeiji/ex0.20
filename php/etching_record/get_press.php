<?php
require_once __DIR__ . "/common.php";

// エッチング評価記録：選んだ押出の詳細
//   基本情報（nBn・穴数を含む）、エッチングの日（t_press.etching_check_date）、
//   状態・担当者・確認者・写真・メモ（t_press_sub。複数行あるときはいちばん古い行）、担当者の選択肢、記録（t_etching）

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) srFail(400, "id が不正です");

try {
    $press = srPressInfo($pdo, $id);
    if (!$press) srFail(404, "押出が見つかりません");

    $stmt = $pdo->prepare("SELECT DATE_FORMAT(etching_check_date, '%Y-%m-%d') FROM t_press WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $press["check_date"] = $stmt->fetchColumn() ?: null;

    $stmt = $pdo->prepare("
        SELECT etching_finish, etching_staff, etching_check_staff, etching_file_url, etching_note
        FROM t_press_sub WHERE press_id = :id ORDER BY id LIMIT 1
    ");
    $stmt->execute([":id" => $id]);
    $sub = $stmt->fetch() ?: [];
    $toStaff = fn($v) => empty($v) ? null : (int)$v;   // ex0.11 は「選んでいない」を 0 で入れていた
    $press["finish"]         = (int)($sub["etching_finish"] ?? 0);
    $press["staff_id"]       = $toStaff($sub["etching_staff"] ?? null);
    $press["check_staff_id"] = $toStaff($sub["etching_check_staff"] ?? null);
    $press["note"]           = $sub["etching_note"] ?? "";
    $photo = $sub["etching_file_url"] ?? "";
    $press["photo"] = ($photo === "" || $photo === "No_image.jpg") ? null : $photo;

    // 担当者の選択肢：在籍中の 作業者・検査員・管理者。前に記録した人が今は対象外でも選べるように加える
    $stmt = $pdo->prepare("
        SELECT id, staff_name, role FROM m_staff
        WHERE (role IN ('operator', 'inspector', 'admin') AND leave_at IS NULL) OR id IN (:s1, :s2)
        ORDER BY FIELD(role, 'operator', 'inspector', 'admin'), emploee_number
    ");
    $stmt->execute([":s1" => $press["staff_id"] ?? 0, ":s2" => $press["check_staff_id"] ?? 0]);
    $staff = array_map(fn($r) => ["id" => (int)$r["id"], "name" => $r["staff_name"], "role" => $r["role"]], $stmt->fetchAll());

    $cols = implode(", ", array_map(fn($c) => "{$c[0]}, {$c[1]}", ER_ROUND_COLS));
    $stmt = $pdo->prepare("SELECT id, Position, {$cols} FROM t_etching WHERE press_id = :id ORDER BY id");
    $stmt->execute([":id" => $id]);
    $records = array_map(function ($r) {
        $rounds = [];
        foreach (["1st", "2nd", "3rd", "4th", "5th"] as $k) {
            $rounds[] = ["jug" => (int)($r["{$k}_jug"] ?? 3), "code" => (int)($r["{$k}_code"] ?? ER_NO_CODE)];
        }
        return ["id" => (int)$r["id"], "position" => $r["Position"], "rounds" => $rounds];
    }, $stmt->fetchAll());

    echo json_encode(["press" => $press, "staff" => $staff, "records" => $records], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    srFail(500, $e->getMessage());
}
