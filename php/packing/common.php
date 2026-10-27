<?php
// 梱包入力（packing.html）の共通処理
//   exd11 PackingV1（＋ PackingMakeingBoxList / PackingAddBoxNumber / DailiReport_OrderSheet）の移植。
//   テーブルは ex0.11 と同じものをそのまま使う（ex0.11 と並べて使えるように）：
//     t_packing             梱包作業（発注書・日付・開始〜終了）
//     t_packing_worker      梱包作業の作業者（m_staff）
//     t_packing_box_number  箱番号（発注書ごと。「発注番号-01/20」）
//     t_packing_box         箱に入れた本数（梱包作業 × 箱 × 時効ラック × 本数）
//     t_press_quality       梱包で見つけた NG（process_id = 4）

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";

const PK_PROCESS = 4;   // m_quality_code_check_process：4 = 梱包

function pkFail(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(["success" => false, "error" => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function pkJsonBody(): array {
    $body = json_decode(file_get_contents("php://input"), true);
    if (!is_array($body)) pkFail(400, "不正なリクエストです");
    return $body;
}

function pkIsDate($s): bool {
    if (!is_string($s) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) return false;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) && (int)$m[1] >= 2000;
}

// 時刻 "H:MM" / "HH:MM" → "HH:MM:00"。不正なら null
function pkTime($s): ?string {
    if (!is_string($s) || !preg_match('/^(\d{1,2}):(\d{2})$/', $s, $m)) return null;
    if ((int)$m[1] > 23 || (int)$m[2] > 59) return null;
    return sprintf("%02d:%02d:00", $m[1], $m[2]);
}

function pkInt($v): int {
    return is_numeric($v) ? (int)$v : 0;
}

// 時効ラックの本数：OK本数（押出本数 − NG の合計。ex0.11 と同じく全工程の NG を引く）と梱包済みの本数
function pkRackQty(PDO $pdo, int $rackId): ?array {
    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.t_press_id,
            IFNULL(r.work_quantity, 0)
              - IFNULL((SELECT SUM(q.ng_quantities) FROM t_press_quality q WHERE q.using_aging_rack_id = r.id), 0) AS ok_qty,
            IFNULL((SELECT SUM(b.work_quantity) FROM t_packing_box b WHERE b.using_aging_rack_id = r.id), 0) AS packed_qty
        FROM t_using_aging_rack r
        WHERE r.id = :id
    ");
    $stmt->execute([":id" => $rackId]);
    $r = $stmt->fetch();
    if (!$r) return null;
    return ["id" => (int)$r["id"], "press_id" => (int)$r["t_press_id"], "ok_qty" => (int)$r["ok_qty"], "packed_qty" => (int)$r["packed_qty"]];
}

// 箱番号を発注書から作る：「発注番号-連番/総数」（総数が10以上なら連番は2桁 01, 02…）。ex0.11 と同じ
function pkMakeBoxNames(string $ordersheetNumber, int $boxCount): array {
    $names = [];
    for ($i = 1; $i <= $boxCount; $i++) {
        $no = $boxCount >= 10 ? sprintf("%02d", $i) : (string)$i;
        $names[] = "{$ordersheetNumber}-{$no}/{$boxCount}";
    }
    return $names;
}
