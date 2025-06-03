<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // アップロードされたファイルがあるか確認
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        // ファイル情報を取得
        $uploadedFile = $_FILES['file'];

        // 日付＋時間形式で新しいファイル名を生成
        $timestamp = date('Ymd_His'); // 年月日_時分秒
        $originalExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION); // 元の拡張子を取得
        $newFileName = $timestamp . '.' . $originalExtension; // 新しいファイル名

        $uploadDir = '../../upload/02_die_maitenance/'; // 保存先ディレクトリ
        
        // ディレクトリがない場合は作成
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // ファイルを保存
        $uploadFilePath = $uploadDir . $newFileName;
        if (move_uploaded_file($uploadedFile['tmp_name'], $uploadFilePath)) {
            echo json_encode(['message' => 'Success file upload', 'fileName' => $newFileName]);
        } else {
            echo json_encode(['message' => 'fail to file upload']);
        }
    } else {
        echo json_encode(['message' => 'fail to file upload']);
    }
} else {
    echo json_encode(['message' => 'request is not currect']);
}
?>
