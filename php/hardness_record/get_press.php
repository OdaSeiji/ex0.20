<?php
require_once __DIR__ . "/../sample_record/common.php";

// 硬さ測定記録：選んだ押出の詳細
//   基本情報（nBn・穴数を含む）、品番の硬さの基準（m_production_numbers.hardness / hardness_note）、
//   測定日（t_press.hardness_check_date）、完了（t_press_sub.hardness_finish）、記録（t_hardness）

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) srFail(400, "id が不正です");

try {
    $press = srPressInfo($pdo, $id);
    if (!$press) srFail(404, "押出が見つかりません");

    $stmt = $pdo->prepare("
        SELECT
            pn.hardness, pn.hardness_note,
            DATE_FORMAT(p.hardness_check_date, '%Y-%m-%d') AS check_date,
            IFNULL((SELECT MAX(s.hardness_finish) FROM t_press_sub s WHERE s.press_id = p.id), 0) AS finish
        FROM t_press p
        LEFT JOIN m_production_numbers pn ON pn.id = :pn
        WHERE p.id = :id
    ");
    $stmt->execute([":pn" => $press["production_number_id"], ":id" => $id]);
    $extra = $stmt->fetch();
    $press["hardness"]      = $extra["hardness"] === null ? null : (float)$extra["hardness"];
    $press["hardness_note"] = $extra["hardness_note"];
    $press["check_date"]    = $extra["check_date"];
    $press["finish"]        = (int)$extra["finish"];

    $stmt = $pdo->prepare("
        SELECT id, position, `1hn`, `2hn`, `3hn`, `4hn`, `5hn`, `6hn`, `7hn`, `8hn`, `9hn`, `10hn`
        FROM t_hardness
        WHERE press_id = :id
        ORDER BY id
    ");
    $stmt->execute([":id" => $id]);
    $records = array_map(function ($r) {
        $values = [];
        for ($i = 1; $i <= 10; $i++) $values[] = $r["{$i}hn"] ?? "";
        return ["id" => (int)$r["id"], "position" => $r["position"], "values" => $values];
    }, $stmt->fetchAll());

    echo json_encode(["press" => $press, "records" => $records], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    srFail(500, $e->getMessage());
}
