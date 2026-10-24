<?php
require_once __DIR__ . "/../sample_record/common.php";

// 粗さ測定記録：選んだ押出の詳細
//   基本情報（nBn・穴数を含む）、品番の粗さの基準（m_measurement_data。同じ品番に複数あるときは新しい行）、
//   測定日・担当者（t_press.measurement_check_date / measurement_staff）、担当者の選択肢、記録（t_measurement）
//   値は 1つの位置に 面1〜3 ×（Rz, 型筋）の6つ ＋ 備考。並びは rz1, die_mark_1, rz2, die_mark_2, rz3, die_mark_3

const RR_COLS = ["rz1", "die_mark_1", "rz2", "die_mark_2", "rz3", "die_mark_3"];

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) srFail(400, "id が不正です");

try {
    $press = srPressInfo($pdo, $id);
    if (!$press) srFail(404, "押出が見つかりません");

    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(measurement_check_date, '%Y-%m-%d') AS check_date, measurement_staff
        FROM t_press WHERE id = :id
    ");
    $stmt->execute([":id" => $id]);
    $extra = $stmt->fetch();
    $press["check_date"] = $extra["check_date"];
    $press["staff_id"]   = $extra["measurement_staff"] === null ? null : (int)$extra["measurement_staff"];

    // 基準（数字でない・空欄は判定しない → null）
    $press["std"] = array_fill(0, 6, null);
    $press["std_note"] = null;
    if ($press["production_number_id"]) {
        $stmt = $pdo->prepare("
            SELECT rz1, die_mark_1, rz2, die_mark_2, rz3, die_mark_3, note
            FROM m_measurement_data
            WHERE production_number_id = :pn
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([":pn" => $press["production_number_id"]]);
        if ($s = $stmt->fetch()) {
            $press["std"] = array_map(fn($c) => is_numeric(trim((string)$s[$c])) ? (float)$s[$c] : null, RR_COLS);
            $press["std_note"] = $s["note"];
        }
    }

    // 担当者の選択肢：在籍中の検査員。前に記録した人が今は対象外でも選べるように加える
    $stmt = $pdo->prepare("
        SELECT id, staff_name FROM m_staff
        WHERE (role = 'inspector' AND leave_at IS NULL) OR id = :sid
        ORDER BY id
    ");
    $stmt->execute([":sid" => $press["staff_id"] ?? 0]);
    $staff = array_map(fn($r) => ["id" => (int)$r["id"], "name" => $r["staff_name"]], $stmt->fetchAll());

    $stmt = $pdo->prepare("
        SELECT id, position, rz1, die_mark_1, rz2, die_mark_2, rz3, die_mark_3, note
        FROM t_measurement
        WHERE press_id = :id
        ORDER BY id
    ");
    $stmt->execute([":id" => $id]);
    $records = array_map(fn($r) => [
        "id"       => (int)$r["id"],
        "position" => $r["position"],
        "values"   => array_map(fn($c) => $r[$c] ?? "", RR_COLS),
        "note"     => $r["note"] ?? "",
    ], $stmt->fetchAll());

    echo json_encode(["press" => $press, "staff" => $staff, "records" => $records], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    srFail(500, $e->getMessage());
}
