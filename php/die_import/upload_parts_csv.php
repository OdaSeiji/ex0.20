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

$dnIdx    = array_search("die_number", $header);
$note2Idx = array_search("note2",      $header);

if ($dnIdx === false) {
    fclose($handle);
    echo json_encode(["status" => "error", "message" => "ヘッダーに die_number が必要です"]);
    exit;
}

$stmtFindDie = $pdo->prepare("SELECT id FROM m_dies WHERE die_number = ?");
$stmtCheckTmp = $pdo->prepare("SELECT id FROM t_parts_import_tmp WHERE die_number = ?");
$stmtInsert  = $pdo->prepare("
    INSERT INTO t_parts_import_tmp (die_number, die_id, note2, created_at)
    VALUES (?, ?, ?, CURDATE())
");

$inserted = 0;
$skipped  = 0;
$errors   = [];

while (($row = fgetcsv($handle)) !== false) {
    $dieNumber = strtoupper(trim($row[$dnIdx] ?? ""));
    $note2     = ($note2Idx !== false) ? trim($row[$note2Idx] ?? "") : "";
    if ($note2 === "") $note2 = null;

    if ($dieNumber === "") continue;

    // m_dies に存在しない → 拒否
    $stmtFindDie->execute([$dieNumber]);
    $dieId = $stmtFindDie->fetchColumn();
    if (!$dieId) {
        $skipped++;
        $errors[] = "{$dieNumber}: m_dies に存在しない金型番号です";
        continue;
    }

    // インポート待ちリストに既に存在 → スキップ
    $stmtCheckTmp->execute([$dieNumber]);
    if ($stmtCheckTmp->fetchColumn()) {
        $skipped++;
        $errors[] = "{$dieNumber}: インポート待ちリストに既に存在します";
        continue;
    }

    try {
        $stmtInsert->execute([$dieNumber, $dieId, $note2]);
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
