<?php
ini_set('display_errors', '0');
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$input = json_decode(file_get_contents("php://input"), true);
$ids   = $input["ids"] ?? [];

if (empty($ids)) {
    echo json_encode(["status" => "error", "message" => "対象が選択されていません"]);
    exit;
}

$okCount = 0;
$errors  = [];

$stmtGet  = $pdo->prepare("SELECT * FROM t_parts_import_tmp WHERE id = ?");
$stmtHO   = $pdo->prepare("INSERT INTO t_die_handover (die_id, is_accessory_item_flag, note2) VALUES (?, 1, ?)");
$stmtDone = $pdo->prepare("DELETE FROM t_parts_import_tmp WHERE id = ?");
$stmtErr  = $pdo->prepare("UPDATE t_parts_import_tmp SET import_error = ? WHERE id = ?");

foreach ($ids as $id) {
    $stmtGet->execute([$id]);
    $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
    if (!$row) continue;

    try {
        $pdo->beginTransaction();

        $stmtHO->execute([$row["die_id"], $row["note2"]]);

        $stmtDone->execute([$id]);
        $pdo->commit();
        $okCount++;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = $e->getMessage();
        $errors[] = "{$row['die_number']}: {$msg}";
        $stmtErr->execute([$msg, $id]);
    }
}

echo json_encode([
    "status"   => "ok",
    "ok_count" => $okCount,
    "errors"   => $errors,
]);
