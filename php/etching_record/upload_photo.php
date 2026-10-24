<?php
require_once __DIR__ . "/common.php";

// エッチング評価記録：写真を1枚添付する（前の写真と入れ替え）。POST multipart: press_id, photo
//   画面で JPEG に縮めてから送る。ファイル名は ex0.11 と違い「etch_押出id_日時.jpg」にして、別の写真と名前がぶつからないようにする
//   ファイル名は t_press_sub.etching_file_url に入れる（ex0.11 の画面からも同じ写真が見える）

$pressId = (int)($_POST["press_id"] ?? 0);
if ($pressId <= 0) srFail(400, "press_id が不正です");
$f = $_FILES["photo"] ?? null;
if (!$f || $f["error"] !== UPLOAD_ERR_OK) srFail(400, "写真を受け取れませんでした");
if ($f["size"] > 15 * 1024 * 1024) srFail(400, "写真が大きすぎます（15MB まで）");
$info = @getimagesize($f["tmp_name"]);
$ext = [IMAGETYPE_JPEG => "jpg", IMAGETYPE_PNG => "png", IMAGETYPE_WEBP => "webp"][$info[2] ?? 0] ?? null;
if (!$ext) srFail(400, "写真は JPEG / PNG / WebP にしてください");

try {
    if (!srPressInfo($pdo, $pressId)) srFail(404, "押出が見つかりません");
    if (!is_dir(ER_PHOTO_DIR) && !mkdir(ER_PHOTO_DIR, 0777, true)) srFail(500, "写真の保存先を作れません");
    $name = sprintf("etch_%d_%s.%s", $pressId, date("YmdHis"), $ext);
    if (!move_uploaded_file($f["tmp_name"], ER_PHOTO_DIR . "/" . $name)) srFail(500, "写真を保存できませんでした");
    srSetPressSub($pdo, $pressId, "etching_file_url", $name);
    echo json_encode(["success" => true, "photo" => $name], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    srFail(500, $e->getMessage());
}
