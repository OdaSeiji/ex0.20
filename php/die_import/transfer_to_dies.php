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

$stmtGet   = $pdo->prepare("SELECT * FROM t_dies_import_tmp WHERE id = ?");
$stmtCheck = $pdo->prepare("SELECT id FROM m_dies WHERE die_number = ?");
$stmtIns      = $pdo->prepare("
    INSERT INTO m_dies
        (die_number, budget_id, production_number_id, die_diamater_id,
         billet_size_id, bolstar_id, created_at)
    VALUES (?, ?, ?, ?, ?, ?, CURDATE())
");
$stmtHO       = $pdo->prepare("INSERT INTO t_die_handover (die_id) VALUES (?)");
$stmtProg     = $pdo->prepare("INSERT INTO t_die_handover_progress (die_id) VALUES (?)");
$stmtDone     = $pdo->prepare("UPDATE t_dies_import_tmp SET import_flag = 1, import_error = NULL WHERE id = ?");
$stmtErr      = $pdo->prepare("UPDATE t_dies_import_tmp SET import_error = ? WHERE id = ?");

foreach ($ids as $id) {
    $stmtGet->execute([$id]);
    $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
    if (!$row) continue;

    /* 品番必須チェック */
    if (!$row["production_number_id"]) {
        $msg = "品番が未設定です";
        $errors[] = "{$row['die_number']}: {$msg}";
        $stmtErr->execute([$msg, $id]);
        continue;
    }

    /* m_dies 重複チェック */
    $stmtCheck->execute([$row["die_number"]]);
    if ($stmtCheck->fetchColumn()) {
        $msg = "m_dies に既に存在します";
        $errors[] = "{$row['die_number']}: {$msg}";
        $stmtErr->execute([$msg, $id]);
        continue;
    }

    try {
        $pdo->beginTransaction();

        $stmtIns->execute([
            $row["die_number"],
            $row["budget_id"]            ?: null,
            $row["production_number_id"],
            $row["die_diamater_id"]      ?: null,
            $row["billet_size_id"]       ?: null,
            $row["bolstar_id"]           ?: null,
        ]);
        $newDieId = $pdo->lastInsertId();

        $stmtHO->execute([$newDieId]);
        $stmtProg->execute([$newDieId]);

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
