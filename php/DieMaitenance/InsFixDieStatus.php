<?php
// 25/6/7 made
  $userid = "webuser";
  $passwd = "";

  // $tableData = json_decode($_POST['tableData']);
  // print($_POST["dieId"]);


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

    $sql = 
      "INSERT INTO t_dies_status (dies_id, do_sth_at, die_status_id, 
        created_at, staff_id, note)
        VALUES(:dies_id, NOW(), :die_status_id, 
        NOW(), :staff_id, :note)";
    $prepare = $dbh->prepare($sql);
    $prepare->bindValue(":dies_id", (INT)$_POST["dieId"], PDO::PARAM_INT);
    $prepare->bindValue(":die_status_id", (INT)$_POST["dieStatus"], PDO::PARAM_INT);
    $prepare->bindValue(":staff_id", (INT)$_POST["staffId"], PDO::PARAM_INT);
    $prepare->bindValue(":note", $_POST["note"], PDO::PARAM_STR);
    $prepare->execute();

    $sql = 
      "
        SELECT 
          t_dies_status.id
        FROM t_dies_status
        ORDER BY id DESC
        LIMIT 1
        ;
      ";
    $prepare = $dbh->prepare($sql);
    $prepare->execute();
    $result = $prepare->fetch(PDO::FETCH_ASSOC);
    echo json_encode($result);
  } catch (PDOException $e){
    $error = $e->getMessage();
    print_r($error);
  }
  $dbh = null;
?>
