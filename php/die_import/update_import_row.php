<?php
ini_set('display_errors', '0');
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$input = json_decode(file_get_contents("php://input"), true);
$id    = $input["id"] ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID required"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE t_dies_import_tmp SET
            die_number           = ?,
            budget_id            = ?,
            production_number_id = ?,
            die_diamater_id      = ?,
            billet_size_id       = ?,
            bolstar_id           = ?,
            import_error         = NULL,
            updated_at           = CURDATE()
        WHERE id = ?
    ");
    $stmt->execute([
        $input["die_number"]           ?: null,
        $input["budget_id"]            ?: null,
        $input["production_number_id"] ?: null,
        $input["die_diamater_id"]      ?: null,
        $input["billet_size_id"]       ?: null,
        $input["bolstar_id"]           ?: null,
        $id,
    ]);
    echo json_encode(["status" => "ok"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
