<?php
ini_set('display_errors', '0');
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

if (empty($_FILES["csv"]["tmp_name"])) {
    echo json_encode(["status" => "error", "message" => "ファイルが選択されていません"]);
    exit;
}

$handle = fopen($_FILES["csv"]["tmp_name"], "r");
if (!$handle) {
    echo json_encode(["status" => "error", "message" => "ファイルを開けませんでした"]);
    exit;
}

$header = fgetcsv($handle);
if ($header && isset($header[0])) {
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
}
$header = array_map('trim', $header);

$dnIdx = array_search("die_number",           $header);
$flIdx = array_search("is_accessory_item_flag", $header);

if ($dnIdx === false || $flIdx === false) {
    fclose($handle);
    echo json_encode(["status" => "error", "message" => "ヘッダーに die_number と is_accessory_item_flag が必要です"]);
    exit;
}

$stmtCheckDies = $pdo->prepare("SELECT id FROM m_dies WHERE die_number = ?");
$stmtCheckTmp  = $pdo->prepare("SELECT id FROM t_dies_import_tmp WHERE die_number = ? AND import_flag = 0");
$stmtInsert    = $pdo->prepare("
    INSERT INTO t_dies_import_tmp (die_number, import_flag, created_at)
    VALUES (?, 0, CURDATE())
");

$inserted = 0;
$skipped  = 0;
$errors   = [];

while (($row = fgetcsv($handle)) !== false) {
    $dieNumber = strtoupper(trim($row[$dnIdx] ?? ""));
    $flag      = trim($row[$flIdx] ?? "");

    if ($dieNumber === "") continue;
    if ($flag !== "1") { $skipped++; continue; }

    $stmtCheckDies->execute([$dieNumber]);
    if ($stmtCheckDies->fetchColumn()) {
        $skipped++;
        $errors[] = "{$dieNumber}: m_dies に既に登録済み";
        continue;
    }

    $stmtCheckTmp->execute([$dieNumber]);
    if ($stmtCheckTmp->fetchColumn()) {
        $skipped++;
        $errors[] = "{$dieNumber}: インポート待ちリストに既に存在";
        continue;
    }

    try {
        $stmtInsert->execute([$dieNumber]);
        $inserted++;
    } catch (PDOException $e) {
        $errors[] = "{$dieNumber}: " . $e->getMessage();
    }
}

fclose($handle);

echo json_encode([
    "status"   => "ok",
    "inserted" => $inserted,
    "skipped"  => $skipped,
    "errors"   => $errors,
]);
