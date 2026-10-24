<?php
// サンプル測定の記録（硬度・粗さ・エッチング）の画面で共通に使う処理
//   exd11 の HardNessV3 / MeasurementV2 / EtchingV5 の移植。どの画面も「押出を選ぶ → サンプル位置ごとに値を入れる」形。
//   サンプル位置の名前（1H, 1E, 1A…）は画面側（scr/sample_position.js）で、押出指示書の nBn と金型の穴数から作る。

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";

// 一覧に出す件数（新しい押出から）
const SR_LIST_LIMIT = 350;

function srFail(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(["success" => false, "error" => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function srJsonBody(): array {
    $body = json_decode(file_get_contents("php://input"), true);
    if (!is_array($body)) srFail(400, "不正なリクエストです");
    return $body;
}

function srIsDate($s): bool {
    if (!is_string($s) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) return false;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) && (int)$m[1] >= 2000;
}

// 測定日の確認：形が正しく、未来の日付でないこと（今日は DB の CURDATE()。php.ini の時刻帯は現場と違うため）
function srCheckMeasureDate(PDO $pdo, $d): void {
    if ($d === null) return;
    if (!srIsDate($d)) srFail(400, "測定日が不正です");
    if ($d > $pdo->query("SELECT CURDATE()")->fetchColumn()) srFail(400, "測定日に未来の日付は入れられません");
}

// 押出の基本情報（画面の右上に出す）
//   nBn は、その押出の押出指示書（t_press.press_directive_id）から取る。
//   指示書が無いときは、ex0.11 と同じく その型のいちばん新しい押出指示書 から取る。
//   品番は、押出の品番バリアントがあればそれ、無ければ型番の既定品番
function srPressInfo(PDO $pdo, int $pressId): ?array {
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.dies_id,
            DATE_FORMAT(p.press_date_at, '%Y-%m-%d') AS press_date,
            p.press_machine_no                       AS machine,
            t.pressing_type,
            p.actual_billet_quantities               AS billets,
            d.die_number,
            d.hole,
            COALESCE(pnv.id, pnd.id)                 AS production_number_id,
            COALESCE(pnv.production_number, pnd.production_number) AS production_number,
            COALESCE(
                (SELECT n.nbn FROM t_press_directive pd JOIN m_nbn n ON n.id = pd.nbn_id WHERE pd.id = p.press_directive_id),
                (SELECT n.nbn FROM t_press_directive pd JOIN m_nbn n ON n.id = pd.nbn_id
                  WHERE pd.dies_id = p.dies_id ORDER BY pd.plan_date_at DESC, pd.id DESC LIMIT 1)
            ) AS nbn
        FROM t_press p
        LEFT JOIN m_pressing_type t ON t.id = p.pressing_type_id
        LEFT JOIN m_dies d ON d.id = p.dies_id
        LEFT JOIN m_die_production_number_variants v ON v.id = p.die_production_number_variant_id
        LEFT JOIN m_production_numbers pnv ON pnv.id = v.production_number_id
        LEFT JOIN m_production_numbers pnd ON pnd.id = d.production_number_id
        WHERE p.id = :id
    ");
    $stmt->execute([":id" => $pressId]);
    $r = $stmt->fetch();
    if (!$r) return null;
    foreach (["id", "dies_id", "machine", "billets", "hole", "production_number_id"] as $k) {
        $r[$k] = $r[$k] === null ? null : (int)$r[$k];
    }
    return $r;
}

// t_press_sub の列を1つ更新する（行が無ければ作る）
//   ex0.11 の硬さの保存は毎回 INSERT していたため、同じ押出の行が複数あることがある。そのときは全部の行を更新する
function srSetPressSub(PDO $pdo, int $pressId, string $column, $value): void {
    $allowed = ["hardness_finish", "etching_finish", "etching_staff", "etching_check_staff", "etching_note", "etching_file_url", "etching_image_url"];
    if (!in_array($column, $allowed, true)) throw new InvalidArgumentException("column");
    $stmt = $pdo->prepare("UPDATE t_press_sub SET `{$column}` = :v WHERE press_id = :id");
    $stmt->execute([":v" => $value, ":id" => $pressId]);
    $exists = $pdo->prepare("SELECT 1 FROM t_press_sub WHERE press_id = :id LIMIT 1");
    $exists->execute([":id" => $pressId]);
    if (!$exists->fetchColumn()) {
        $stmt = $pdo->prepare("INSERT INTO t_press_sub (press_id, `{$column}`) VALUES (:id, :v)");
        $stmt->execute([":id" => $pressId, ":v" => $value]);
    }
}
