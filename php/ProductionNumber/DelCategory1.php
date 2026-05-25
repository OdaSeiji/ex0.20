<?php
  ini_set('display_errors', '0');
  $userid = "webuser";
  $passwd = "";

  try {
    $dbh = new PDO(
      'mysql:host=localhost; dbname=extrusion; charset=utf8',
      $userid,
      $passwd,
      array(
        PDO::ATTR_ERRMODE      => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
      )
    );

    $id = (int)($_POST['id'] ?? 0);
    if ($id === 0) {
      echo json_encode(["status" => "error", "message" => "IDが不正です"]);
      exit;
    }

    $check = $dbh->prepare("SELECT COUNT(*) AS cnt FROM m_production_numbers_category2 WHERE category1_id = ?");
    $check->execute([$id]);
    $cnt = (int)$check->fetchColumn();
    if ($cnt > 0) {
      echo json_encode(["status" => "error", "message" => "カテゴリ2が紐づいているため削除できません ({$cnt}件)"]);
      exit;
    }

    $stmt = $dbh->prepare("DELETE FROM m_production_numbers_category1 WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["status" => "ok"]);
  } catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
  }
  $dbh = null;
?>
