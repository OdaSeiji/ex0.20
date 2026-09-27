<?php
// 品質評価入力（quality_report.html）の共通処理
//   exd11 QualityReportV7 のリファイン版。t_press_quality にラックごとの NG（工程・コード・本数・判定者・備考）を記録し、
//   寸法の完了日と QC の確認（確認日・確認者）を t_press に保存する。

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../db.php";

// 一覧に出す期間：今日から1か月前まで（それより前の押出は exd11 V7 で扱う）
const QR_WINDOW_SQL = "p.press_date_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";

// QC 完了：新しい確認日が入っている、または旧画面（V7）で qc_check_id が入っている
const QR_DONE_SQL = "(p.qc_check_date IS NOT NULL OR IFNULL(p.qc_check_id, 0) > 0)";

// NG を入力できる工程（m_quality_code_check_process：1=寸法, 2=エッチング, 3=時効。4=梱包は梱包画面が扱う）
const QR_PROCESSES = [1, 2, 3];

// プレス種別（m_pressing_type）：1=〇（テスト）, 2=◎, 3=●
const QR_TYPE_TEST = 1;

function qrFail(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(["success" => false, "error" => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function qrJsonBody(): array {
    $body = json_decode(file_get_contents("php://input"), true);
    if (!is_array($body)) qrFail(400, "不正なリクエストです");
    return $body;
}

function qrIsDate($s): bool {
    return is_string($s) && (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
}

// 在籍中の検査員か（NG の判定者・QC の確認者は m_staff の inspector に限る）
function qrIsActiveInspector(PDO $pdo, int $staffId): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM m_staff WHERE id = :id AND role = 'inspector' AND leave_at IS NULL");
    $stmt->execute([":id" => $staffId]);
    return (bool)$stmt->fetchColumn();
}

// 押出が一覧の期間内にあるか（期間外は古いページで扱うので、更新も受け付けない）
function qrPressInWindow(PDO $pdo, int $pressId): ?array {
    $stmt = $pdo->prepare("
        SELECT p.id, p.pressing_type_id, p.dimension_check_date, p.etching_check_date, p.hardness_check_date
        FROM t_press p
        WHERE p.id = :id AND " . QR_WINDOW_SQL
    );
    $stmt->execute([":id" => $pressId]);
    return $stmt->fetch() ?: null;
}

// QC に必要な工程のうち、完了日が入っていないもの（〇 は寸法のみ、◎● は寸法・エッチング・時効）
function qrMissingSteps(array $press): array {
    $required = ["dimension_check_date" => "dimension"];
    if ((int)$press["pressing_type_id"] !== QR_TYPE_TEST) {
        $required["etching_check_date"]  = "etching";
        $required["hardness_check_date"] = "aging";
    }
    $missing = [];
    foreach ($required as $col => $name) {
        if (empty($press[$col]) || $press[$col] === "0000-00-00") $missing[] = $name;
    }
    return $missing;
}
