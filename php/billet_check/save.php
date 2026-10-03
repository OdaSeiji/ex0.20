<?php
// 棚卸しの保存。id があれば更新、無ければ新規。
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";
require_once "columns.php";

$body    = json_decode(file_get_contents("php://input"), true) ?? [];
$id      = (int)($body["id"] ?? 0);
$staffId = (int)($body["staff_id"] ?? 0);
$checkAt = (string)($body["check_at"] ?? "");
$sizeId  = (int)($body["billet_size_id"] ?? 0);
$values  = $body["values"] ?? [];

if ($staffId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkAt) || $sizeId <= 0) {
    echo json_encode(["ok" => false, "message" => "担当者・棚卸し日・太さを選んでください"], JSON_UNESCAPED_UNICODE);
    exit;
}

$is9 = ($sizeId === SIZE_ID_9INCH);
$data = [
    "staff_id"       => $staffId,
    "check_at"       => $checkAt,
    "billet_size_id" => $sizeId,
    "A6N01228600"    => 0,   // 6N01 は 6N01A と同じ材質。列は使わない
    "A6N012281200"   => 0,
];
foreach (BILLET_COLUMNS as $key => [$col, $material, $length, $vn]) {
    if ($vn && !$is9) {
        $data[$col] = ($length === 6000) ? null : 0;   // ex0.11 と同じく 12・14インチの VN は 0
        continue;
    }
    $v = $values[$key] ?? "";
    if ($v === "" || !preg_match('/^\d+$/', (string)$v)) {
        echo json_encode(["ok" => false, "message" => "本数は 0 以上の整数で、すべての欄に入れてください"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $data[$col] = (int)$v;
}

try {
    if ($id > 0) {
        $sets = implode(", ", array_map(fn($c) => "`{$c}` = :{$c}", array_keys($data)));
        $stmt = $pdo->prepare("UPDATE t_checkbillet SET {$sets} WHERE id = :id");
        $stmt->execute(array_merge($data, ["id" => $id]));
    } else {
        $cols = implode(", ", array_map(fn($c) => "`{$c}`", array_keys($data)));
        $phs  = implode(", ", array_map(fn($c) => ":{$c}", array_keys($data)));
        $stmt = $pdo->prepare("INSERT INTO t_checkbillet ({$cols}) VALUES ({$phs})");
        $stmt->execute($data);
        $id = (int)$pdo->lastInsertId();
    }
    echo json_encode(["ok" => true, "id" => $id], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["ok" => false, "message" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
