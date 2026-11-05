<?php
// 品番管理：選んだ品番のカテゴリ2（m_production_numbers.production_category2_id）をまとめて変える
//   POST JSON: { ids: [品番の id, ...], category2_id }
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";

$body  = json_decode(file_get_contents("php://input"), true);
$ids   = array_values(array_unique(array_filter(array_map("intval", (array)($body["ids"] ?? [])), fn($v) => $v > 0)));
$cat2  = (int)($body["category2_id"] ?? 0);

if (!$ids || $cat2 <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "品番とカテゴリ2を選んでください"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $chk = $pdo->prepare("SELECT 1 FROM m_production_numbers_category2 WHERE id = ?");
    $chk->execute([$cat2]);
    if (!$chk->fetchColumn()) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "カテゴリ2が見つかりません"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $place = implode(",", array_fill(0, count($ids), "?"));
    $stmt  = $pdo->prepare("UPDATE m_production_numbers SET production_category2_id = ? WHERE id IN ({$place})");
    $stmt->execute(array_merge([$cat2], $ids));
    echo json_encode(["status" => "ok", "updated" => $stmt->rowCount()], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
