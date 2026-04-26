<?php
header("Content-Type: application/json; charset=UTF-8");

// DB接続
require_once "./../db.php";

try {
    // POST受け取り
    $press_id            = $_POST["press_id"];
    $inspection_date     = $_POST["inspection_date"];
    $inspected_by        = $_POST["inspected_by"];
    $inspection_result   = $_POST["inspection_result"];
    $dimension_result    = $_POST["dimension_result"];
    $shape_result        = $_POST["shape_result"];
    $inspection_comment  = $_POST["inspection_comment"];

    // ▼ 押出記録から die_id を取得（t_press.dies_id）
    $sql_die = "SELECT dies_id FROM t_press WHERE id = ?";
    $stmt = $pdo->prepare($sql_die);
    $stmt->execute([$press_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception("press_id が不正です。");
    }
    $die_id = $row["dies_id"];

    // ▼ 検査記録を保存（寸法・形状を含む）
    $sql_ins = "
        INSERT INTO t_die_inspection (
            die_id, press_id, inspection_date,
            inspected_by, inspection_result,
            dimension_result, shape_result,
            inspection_comment, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ";

    $stmt = $pdo->prepare($sql_ins);
    $stmt->execute([
        $die_id,
        $press_id,
        $inspection_date,
        $inspected_by,
        $inspection_result,
        $dimension_result,
        $shape_result,
        $inspection_comment
    ]);

    $inspection_id = $pdo->lastInsertId();

    // ▼ 添付ファイル処理（複数対応）
    if (!empty($_FILES["file"]["name"][0])) {

        // 保存先フォルダ inspection/{inspection_id}/
        $upload_dir = "../../uploads/inspection/" . $inspection_id . "/";

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 複数ファイルループ
        foreach ($_FILES["file"]["name"] as $i => $original) {

            $tmp_name  = $_FILES["file"]["tmp_name"][$i];
            $file_type = $_FILES["file"]["type"][$i];

            // ファイル名をユニーク化
            $saved = time() . "_" . preg_replace("/[^A-Za-z0-9_\.-]/", "_", $original);
            $path = $upload_dir . $saved;

            // 保存
            if (move_uploaded_file($tmp_name, $path)) {

                // DB登録
                $sql_file = "
                    INSERT INTO t_die_attachment (
                        inspection_id,
                        file_name,
                        original_name,
                        file_type,
                        uploaded_at
                    ) VALUES (
                        ?, ?, ?, ?, NOW()
                    )
                ";

                $stmt = $pdo->prepare($sql_file);
                $stmt->execute([
                    $inspection_id,
                    $saved,
                    $original,
                    $file_type
                ]);
            }
        }
    }

    echo json_encode([
        "status" => "success",
        "inspection_id" => $inspection_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
