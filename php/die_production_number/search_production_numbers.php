<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$dieId   = (int)($_GET["die_id"] ?? 0);
$keyword = isset($_GET["q"]) ? trim($_GET["q"]) : "";

if ($keyword === "") {
    // 未入力時は、既にこの金型に紐づいている品番の先頭7文字に近いものを自動提案する
    $stmt = $pdo->prepare("
        SELECT DISTINCT LEFT(p.production_number, 7) AS prefix
        FROM m_die_production_number_variants v
        JOIN m_production_numbers p ON p.id = v.production_number_id
        WHERE v.die_id = :die_id
    ");
    $stmt->execute([":die_id" => $dieId]);
    $prefixes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$prefixes) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $conditions = [];
    $params = [":die_id" => $dieId];
    foreach ($prefixes as $i => $prefix) {
        $key = ":prefix{$i}";
        $conditions[] = "production_number LIKE {$key}";
        $params[$key] = "{$prefix}%";
    }

    $stmt = $pdo->prepare("
        SELECT id, production_number, production_length
        FROM m_production_numbers
        WHERE (" . implode(" OR ", $conditions) . ")
          AND id NOT IN (
              SELECT production_number_id FROM m_die_production_number_variants WHERE die_id = :die_id
          )
        ORDER BY production_number
        LIMIT 50
    ");
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

// キーワード入力時は通常の部分一致検索
$stmt = $pdo->prepare("
    SELECT id, production_number, production_length
    FROM m_production_numbers
    WHERE production_number LIKE :keyword
      AND id NOT IN (
          SELECT production_number_id FROM m_die_production_number_variants WHERE die_id = :die_id
      )
    ORDER BY production_number
    LIMIT 50
");
$stmt->bindValue(":keyword", "%{$keyword}%", PDO::PARAM_STR);
$stmt->bindValue(":die_id", $dieId, PDO::PARAM_INT);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
