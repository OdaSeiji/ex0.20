<?php
// 25/5/19 made
  $userid = "webuser";
  $passwd = "";

  $tableData = json_decode($_POST['tableData']);

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

    foreach($tableData as $row){
      $sql = 
        "INSERT INTO t_dies_status (dies_id, do_sth_at, tank, die_status_id, created_at)
         VALUES(?, ?, ?, 4, ?)";
      $prepare = $dbh->prepare($sql);
      $prepare->execute([$row[0], $row[1], $row[2], $row[3]]);
    }

    echo json_encode("INSERTED");
  } catch (PDOException $e){
    $error = $e->getMessage();
    print_r($error);
  }
  $dbh = null;
?>
