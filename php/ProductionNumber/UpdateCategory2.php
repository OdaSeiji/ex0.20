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

    $id      = (int)($_POST['id']      ?? 0);
    $name_jp = trim($_POST['name_jp'] ?? '');
    if ($id === 0 || $name_jp === '') {
      echo json_encode(["status" => "error", "message" => "パラメータ不正"]);
      exit;
    }

    $stmt = $dbh->prepare("UPDATE m_production_numbers_category2 SET name_jp = ? WHERE id = ?");
    $stmt->execute([$name_jp, $id]);

    echo json_encode(["status" => "ok"]);
  } catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
  }
  $dbh = null;
?>
