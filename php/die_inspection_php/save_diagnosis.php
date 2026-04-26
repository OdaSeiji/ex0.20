<?php
require_once "./../db.php";

try {
    // POST 受け取り
    $inspection_id      = $_POST["inspection_id"];
    $die_id             = $_POST["die_id"];
    $diagnosis_result   = $_POST["diagnosis_result"];
    $diagnosis_comment  = $_POST["diagnosis_comment"];
    $diagnosed_by       = $_POST["diagnosed_by"];
    $issue_id           = $_POST["issue_id"] ?? null;

    /* ---------------------------------------------------------
       ① t_die_diagnosis に登録（診断日時を NOW() で保存）
    --------------------------------------------------------- */
    $sql = "INSERT INTO t_die_diagnosis
            (inspection_id, die_id, diagnosis_result, diagnosis_comment, diagnosed_by, issue_id, diagnosis_date)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $inspection_id,
        $die_id,
        $diagnosis_result,
        $diagnosis_comment,
        $diagnosed_by,
        $issue_id
    ]);

    // 新しい diagnosis_id を取得
    $diagnosis_id = $pdo->lastInsertId();

    /* ---------------------------------------------------------
       ② 診断画像の保存先フォルダを作成
    --------------------------------------------------------- */
    $dir = "../../uploads/diagnosis/" . $diagnosis_id;

    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    /* ---------------------------------------------------------
       ③ 添付ファイルを保存 & DB 登録
    --------------------------------------------------------- */
    if (!empty($_FILES["files"]["name"][0])) {
        foreach ($_FILES["files"]["tmp_name"] as $i => $tmpName) {

            $original = $_FILES["files"]["name"][$i];
            $fileName = time() . "_" . $original;

            move_uploaded_file($tmpName, $dir . "/" . $fileName);

            // t_die_attachment に登録
            $sql = "INSERT INTO t_die_attachment
                    (diagnosis_id, file_name, original_name)
                    VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$diagnosis_id, $fileName, $original]);
        }
    }

    echo json_encode(["status" => "ok"]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
