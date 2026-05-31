<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$rows = json_decode(file_get_contents("php://input"), true);

if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$inserted = 0;
$errors   = [];

foreach ($rows as $row) {
    $die_id         = intval($row["die_id"]);
    $invoice_number = trim($row["invoice_number"] ?? "");

    // die情報取得（production_number_id が存在しない場合に備えて try-catch）
    try {
        $dieStmt = $pdo->prepare("
            SELECT d.die_number, COALESCE(p.production_number, d.die_number) AS product_code
            FROM m_dies d
            LEFT JOIN m_production_numbers p ON d.production_number_id = p.id
            WHERE d.id = ?
        ");
        $dieStmt->execute([$die_id]);
        $die = $dieStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $dieStmt = $pdo->prepare("SELECT die_number FROM m_dies WHERE id = ?");
        $dieStmt->execute([$die_id]);
        $die = $dieStmt->fetch(PDO::FETCH_ASSOC);
        if ($die) $die["product_code"] = $die["die_number"];
    }

    if (!$die) {
        $errors[] = "die_id={$die_id} が見つかりません";
        continue;
    }

    $die_model_code = $die["die_number"]    ?? "";
    $product_code   = $die["product_code"]  ?? $die_model_code;

    $insStmt = $pdo->prepare("
        INSERT INTO t_die_handover (die_id, die_model_code, product_code, invoice_number)
        VALUES (?, ?, ?, ?)
    ");
    $insStmt->execute([$die_id, $die_model_code, $product_code, $invoice_number]);
    $inserted++;
}

echo json_encode([
    "status"   => "ok",
    "inserted" => $inserted,
    "errors"   => $errors,
], JSON_UNESCAPED_UNICODE);
