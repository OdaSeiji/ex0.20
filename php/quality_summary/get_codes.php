<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// NGコードの説明（品質サマリーの列見出しのツールチップ用）
try {
    $stmt = $pdo->query("
        SELECT quality_code, description_jp, note_jp, description_vn, note_vn
        FROM m_quality_code
        ORDER BY quality_code
    ");
    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
