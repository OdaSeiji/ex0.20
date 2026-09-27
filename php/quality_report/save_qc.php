<?php
require_once __DIR__ . "/common.php";

// QC の確認（最終確認）を保存・取消する
//   POST JSON:
//     { action: "confirm", press_id, date, staff_id }  … 必要な工程の完了日がそろっている場合のみ
//     { action: "cancel",  press_id }                   … 完了を取り消して一覧（未完了）に戻す
//   確認済みにするときは ex0.11 の画面でも確認済みに見えるよう qc_check_id = 1 も入れる

$body    = qrJsonBody();
$action  = $body["action"] ?? "";
$pressId = (int)($body["press_id"] ?? 0);

try {
    $press = qrPressInWindow($pdo, $pressId);
    if (!$press) qrFail(409, "1か月より前の押出は、旧画面で修正してください");

    if ($action === "confirm") {
        $date    = $body["date"] ?? "";
        $staffId = (int)($body["staff_id"] ?? 0);
        if (!qrIsDate($date)) qrFail(400, "確認日を入力してください");
        if (!qrIsActiveInspector($pdo, $staffId)) qrFail(400, "確認者は在籍中の検査員から選んでください");
        $missing = qrMissingSteps($press);
        if ($missing) {
            http_response_code(409);
            echo json_encode(["success" => false, "missing_steps" => $missing, "error" => "完了していない工程があります"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmt = $pdo->prepare("
            UPDATE t_press SET qc_check_date = :date, qc_staff_id = :staff_id, qc_check_id = 1
            WHERE id = :id
        ");
        $stmt->execute([":date" => $date, ":staff_id" => $staffId, ":id" => $pressId]);

    } elseif ($action === "cancel") {
        $stmt = $pdo->prepare("
            UPDATE t_press SET qc_check_date = NULL, qc_staff_id = NULL, qc_check_id = NULL
            WHERE id = :id
        ");
        $stmt->execute([":id" => $pressId]);

    } else {
        qrFail(400, "不正な操作です");
    }
    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    qrFail(500, $e->getMessage());
}
