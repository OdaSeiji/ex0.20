<?php
ini_set('display_errors', '0');
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

if (empty($_FILES["csv"])) {
    echo json_encode(["status" => "error", "message" => "ファイルが選択されていません"]);
    exit;
}

$handle = fopen($_FILES["csv"]["tmp_name"], "r");
if (!$handle) {
    echo json_encode(["status" => "error", "message" => "ファイルを開けませんでした"]);
    exit;
}

/* BOM除去しながらヘッダー行をスキップ */
$header = fgetcsv($handle);
if ($header && isset($header[0])) {
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
}

/* 品番マスタを事前ロード（自動マッチ用） */
$pnMap = [];
foreach ($pdo->query("SELECT id, production_number FROM m_production_numbers")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $pnMap[mb_strtolower(trim($r["production_number"]))] = (int)$r["id"];
}

$stmtCheckDies = $pdo->prepare("SELECT id FROM m_dies WHERE die_number = ?");
$stmtCheckTmp  = $pdo->prepare("SELECT id FROM t_dies_import_tmp WHERE die_number = ? AND import_flag = 0");
$stmtInsert    = $pdo->prepare("
    INSERT INTO t_dies_import_tmp
        (budget_id, die_number, production_number_raw, production_number_id, import_flag, created_at)
    VALUES (?, ?, ?, ?, 0, CURDATE())
");

$inserted = 0;
$skipped  = 0;
$errors   = [];

while (($row = fgetcsv($handle)) !== false) {
    $budgetId  = isset($row[0]) ? trim($row[0]) : null;
    $dieNumber = isset($row[1]) ? trim($row[1]) : null;
    $pnRaw     = isset($row[2]) ? trim($row[2]) : null;

    if (!$dieNumber) continue;

    /* m_dies 重複チェック */
    $stmtCheckDies->execute([$dieNumber]);
    if ($stmtCheckDies->fetchColumn()) {
        $skipped++;
        $errors[] = "{$dieNumber}: m_dies に既に登録済み";
        continue;
    }

    /* tmp 重複チェック（未転送のみ） */
    $stmtCheckTmp->execute([$dieNumber]);
    if ($stmtCheckTmp->fetchColumn()) {
        $skipped++;
        $errors[] = "{$dieNumber}: インポート待ちリストに既に存在";
        continue;
    }

    /* 品番を自動マッチ */
    $pnId = null;
    if ($pnRaw) {
        $pnId = $pnMap[mb_strtolower($pnRaw)] ?? null;
    }

    $stmtInsert->execute([$budgetId ?: null, $dieNumber, $pnRaw ?: null, $pnId]);
    $inserted++;
}

fclose($handle);

echo json_encode([
    "status"   => "ok",
    "inserted" => $inserted,
    "skipped"  => $skipped,
    "errors"   => $errors,
]);
