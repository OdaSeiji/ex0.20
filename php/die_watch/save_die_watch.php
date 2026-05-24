<?php
ini_set('display_errors', '0');
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

try {
    $input  = json_decode(file_get_contents("php://input"), true);
    $action = $input["action"] ?? "insert";

    if ($action === "insert") {
        $sql = "
            INSERT INTO t_die_watch
                (die_id, reason_jp, reason_vn, priority, target_date, memo, registered_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input["die_id"]        ?? null,
            $input["reason_jp"]     ?: null,
            $input["reason_vn"]     ?: null,
            $input["priority"]      ?? "low",
            $input["target_date"]   ?: null,
            $input["memo"]          ?: null,
            $input["registered_by"] ?: null,
        ]);
        echo json_encode(["status" => "ok", "id" => $pdo->lastInsertId()]);

    } elseif ($action === "update") {
        $id        = $input["id"]     ?? null;
        $newStatus = $input["status"] ?? "active";

        if (!$id) {
            echo json_encode(["status" => "error", "message" => "ID required"]);
            exit;
        }

        $closedAt = $newStatus === "closed" ? date("Y-m-d H:i:s") : null;

        $sql = "
            UPDATE t_die_watch SET
                reason_jp   = ?,
                reason_vn   = ?,
                priority    = ?,
                target_date = ?,
                status      = ?,
                memo        = ?,
                closed_at   = ?
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input["reason_jp"]   ?: null,
            $input["reason_vn"]   ?: null,
            $input["priority"]    ?? "low",
            $input["target_date"] ?: null,
            $newStatus,
            $input["memo"]        ?: null,
            $closedAt,
            $id,
        ]);
        echo json_encode(["status" => "ok"]);

    } else {
        echo json_encode(["status" => "error", "message" => "unknown action"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
