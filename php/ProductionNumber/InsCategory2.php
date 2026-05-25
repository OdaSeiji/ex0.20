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

    $name_jp     = trim($_POST['name_jp']      ?? '');
    $category1_id = (int)($_POST['category1_id'] ?? 0);
    if ($name_jp === '' || $category1_id === 0) {
      echo json_encode(["status" => "error", "message" => "パラメータ不正"]);
      exit;
    }

    $stmt = $dbh->prepare("INSERT INTO m_production_numbers_category2 (name_jp, category1_id) VALUES (?, ?)");
    $stmt->execute([$name_jp, $category1_id]);

    echo json_encode(["status" => "ok", "id" => $dbh->lastInsertId()]);
  } catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
  }
  $dbh = null;
?>
