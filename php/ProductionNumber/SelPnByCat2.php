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

    $cat2Id = (int)($_POST['cat2Id'] ?? 0);
    if ($cat2Id === 0) {
      echo json_encode([]);
      exit;
    }

    $stmt = $dbh->prepare("
      SELECT id, production_number
      FROM m_production_numbers
      WHERE production_category2_id = ?
      ORDER BY production_number
    ");
    $stmt->execute([$cat2Id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  } catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
  }
  $dbh = null;
?>
