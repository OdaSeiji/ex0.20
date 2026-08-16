<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$rows = json_decode(file_get_contents("php://input"), true);

if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM m_ordersheet WHERE ordersheet_number = ?");
$insertStmt = $pdo->prepare("
    INSERT INTO m_ordersheet
        (ordersheet_number, production_numbers_id, issue_date_at, delivery_date_at, production_quantity, note, created_at)
    VALUES (?, ?, ?, ?, ?, ?, CURDATE())
");

$inserted = 0;
$skipped = [];

$pdo->beginTransaction();
try {
    foreach ($rows as $row) {
        $ordersheetNumber = trim($row["ordersheet_number"] ?? "");
        $productionNumbersId = intval($row["production_numbers_id"] ?? 0);
        $issueAt = $row["issue_date_at"] ?? "";
        $deliveryAt = $row["delivery_date_at"] ?? "";
        $quantity = intval($row["production_quantity"] ?? 0);
        $note = $row["note"] ?? "";

        if ($ordersheetNumber === "" || !$productionNumbersId || !$issueAt || !$deliveryAt || $quantity <= 0) {
            $skipped[] = $ordersheetNumber ?: "(no number)";
            continue;
        }

        $checkStmt->execute([$ordersheetNumber]);
        if ($checkStmt->fetchColumn() > 0) {
            $skipped[] = $ordersheetNumber;
            continue;
        }

        $insertStmt->execute([$ordersheetNumber, $productionNumbersId, $issueAt, $deliveryAt, $quantity, $note]);
        $inserted++;
    }
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}

echo json_encode(["status" => "ok", "inserted" => $inserted, "skipped" => $skipped], JSON_UNESCAPED_UNICODE);
