<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

$result = ["status" => "error", "message" => "Unknown error"];

try {
  $dbh = new PDO(
    'mysql:host=localhost;dbname=extrusion;charset=utf8',
    "webuser", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
  );

  $id = (int)($_POST['id'] ?? 0);
  if ($id === 0) {
    $result = ["status" => "error", "message" => "IDが不正です"];
  } else {
    // 品番チェック
    $check = $dbh->prepare("SELECT COUNT(*) AS cnt FROM m_production_numbers WHERE production_category2_id = ?");
    $check->execute([$id]);
    $cnt = (int)$check->fetchColumn();
    if ($cnt > 0) {
      $result = ["status" => "error", "message" => "品番が紐づいているため削除できません ({$cnt}件)"];
    } else {
      // 注文書チェック（品番経由）
      $chkOrder = $dbh->prepare("
        SELECT COUNT(*) AS cnt
        FROM m_production_numbers pn
        INNER JOIN m_ordersheet os ON os.production_numbers_id = pn.id
        WHERE pn.production_category2_id = ?
      ");
      $chkOrder->execute([$id]);
      $cntOrder = (int)$chkOrder->fetchColumn();
      if ($cntOrder > 0) {
        $result = ["status" => "error", "message" => "注文書に紐づいた品番があるため削除できません ({$cntOrder}件)"];
      } else {
        $stmt = $dbh->prepare("DELETE FROM m_production_numbers_category2 WHERE id = ?");
        $stmt->execute([$id]);
        $result = ["status" => "ok"];
      }
    }
  }
} catch (Throwable $e) {
  $msg = $e->getMessage();
  // FK制約違反を人間が読めるメッセージに変換
  if (strpos($msg, 'SQLSTATE[23000]') !== false) {
    $msg = "関連データが存在するため削除できません（外部キー制約）";
  }
  $result = ["status" => "error", "message" => $msg];
}

ob_end_clean();
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
