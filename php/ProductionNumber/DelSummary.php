<?php
  /* 21/04/29作成 */

  $userid = "webuser";
  $passwd = "";

  // $vnPdId = $_POST["vnPdId"];
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

    $stmt = $dbh->prepare("DELETE FROM m_production_numbers WHERE id=:targetId");
    $stmt->bindValue(':targetId', (INT)$_POST["targetId"], PDO::PARAM_INT);
    $stmt->execute();

    echo(json_encode("Deleted"));
  } catch (PDOException $e){
    $error = $e->getMessage();
    // $pdh->rollback();
    print_r($error);
  }
  $dbh = null;
?>
