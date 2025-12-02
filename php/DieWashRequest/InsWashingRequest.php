<?php
// 25/10/31 made
  $userid = "webuser";
  $passwd = "";

  // print_r($_POST);

  try{
    $dbh = new PDO(
      'mysql:host=localhost; dbname=extrusion; charset=utf8',
      $userid,
      $passwd,
      array(
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_EMULATE_PREPARES => false
      )
    );

    $sql = "
      INSERT INTO t_application (application_datetime, staff_id, die_id, reason)
      VALUES(NOW(), :staff_id, :die_id, :reason)
    ";
    $prepare = $dbh->prepare($sql);
    $prepare->bindValue(":staff_id", (int)$_POST['applicant_id'], PDO::PARAM_INT);
    $prepare->bindValue(":die_id", (int)$_POST['die_id'], PDO::PARAM_INT);
    $prepare->bindValue(":reason", $_POST['reason'], PDO::PARAM_STR);
    $prepare->execute();

    echo json_encode("INSERTED");
  } catch (PDOException $e){
    $error = $e->getMessage();
    print_r($error);
  }
  $dbh = null;
?>
