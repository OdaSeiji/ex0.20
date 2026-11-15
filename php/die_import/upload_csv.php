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

/* 直径マスタ（mm の数字 → id）とボルスターマスタ（名前 → id・直径 id） */
$diamMap = [];
foreach ($pdo->query("SELECT id, die_diamater FROM m_dies_diamater")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $diamMap[(string)(int)$r["die_diamater"]] = (int)$r["id"];
}
$diamName = array_flip($diamMap); // id → mm
/* ボルスター名は "B4620-1208-**" "B6026-1212--**" のように末尾の書き方がばらばらなので、
   「*」を除き、続いたハイフンを1つにし、前後のハイフンを取ってから比べる（B4620-1208 で書けばよい） */
function bolster_key($s) {
    $s = strtoupper(trim((string)$s));
    $s = str_replace(['*', ' ', '　'], '', $s);
    $s = preg_replace('/-+/', '-', $s);
    return trim($s, '-');
}
$bolsterMap = [];
foreach ($pdo->query("SELECT id, bolster_name, die_diamater FROM m_bolster")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $bolsterMap[bolster_key($r["bolster_name"])] = ["id" => (int)$r["id"], "diam_id" => (int)$r["die_diamater"], "name" => $r["bolster_name"]];
}

$stmtCheckDies = $pdo->prepare("SELECT id FROM m_dies WHERE die_number = ?");
$stmtCheckTmp  = $pdo->prepare("SELECT id FROM t_dies_import_tmp WHERE die_number = ? AND import_flag = 0");
$stmtInsert    = $pdo->prepare("
    INSERT INTO t_dies_import_tmp
        (budget_id, die_number, production_number_raw, production_number_id,
         die_diamater_id, hole, bolstar_id, import_flag, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0, CURDATE())
");
$warnings = [];

$inserted = 0;
$skipped  = 0;
$errors   = [];

while (($row = fgetcsv($handle)) !== false) {
    $budgetId  = isset($row[0]) ? trim($row[0]) : null;
    $dieNumber = isset($row[1]) ? trim($row[1]) : null;
    $pnRaw     = isset($row[2]) ? trim($row[2]) : null;
    $diamRaw   = isset($row[3]) ? trim($row[3]) : '';   // D: 直径 (mm)
    $holeRaw   = isset($row[4]) ? trim($row[4]) : '';   // E: 穴数
    $bolstRaw  = isset($row[5]) ? trim($row[5]) : '';   // F: ボルスター

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

    /* D: 直径。"300" "300mm" "ø300" などから数字を取り出して、マスタの直径に合わせる */
    $diamId = null;
    if ($diamRaw !== '') {
        $mm = preg_replace('/[^0-9.]/', '', $diamRaw);
        if ($mm !== '' && isset($diamMap[(string)(int)round((float)$mm)])) {
            $diamId = $diamMap[(string)(int)round((float)$mm)];
        } else {
            $warnings[] = "{$dieNumber}: 直径「{$diamRaw}」はマスタにありません（空にしました。編集で選んでください）";
        }
    }

    /* E: 穴数（1〜6） */
    $hole = null;
    if ($holeRaw !== '') {
        if (preg_match('/^\d+(\.0+)?$/', $holeRaw) && (int)$holeRaw >= 1 && (int)$holeRaw <= 6) {
            $hole = (int)$holeRaw;
        } else {
            $warnings[] = "{$dieNumber}: 穴数「{$holeRaw}」は 1〜6 の数字ではありません（空にしました）";
        }
    }

    /* F: ボルスター。直径が空ならボルスターから直径を決める。直径と合わなければ知らせる */
    $bolsterId = null;
    if ($bolstRaw !== '') {
        $b = $bolsterMap[bolster_key($bolstRaw)] ?? null;
        if (!$b) {
            $warnings[] = "{$dieNumber}: ボルスター「{$bolstRaw}」はマスタにありません（空にしました。編集で選んでください）";
        } else {
            $bolsterId = $b["id"];
            if ($diamId === null && $diamRaw === '') {
                $diamId = $b["diam_id"];
            } elseif ($diamId !== null && $diamId !== $b["diam_id"]) {
                $warnings[] = "{$dieNumber}: ボルスター {$b['name']} は直径 " . ($diamName[$b["diam_id"]] ?? '?')
                            . "mm 用ですが、直径は {$diamName[$diamId]}mm です（確かめてください）";
            }
        }
    }

    $stmtInsert->execute([$budgetId ?: null, $dieNumber, $pnRaw ?: null, $pnId, $diamId, $hole, $bolsterId]);
    $inserted++;
}

fclose($handle);

echo json_encode([
    "status"   => "ok",
    "inserted" => $inserted,
    "skipped"  => $skipped,
    "errors"   => $errors,
    "warnings" => $warnings,
], JSON_UNESCAPED_UNICODE);
