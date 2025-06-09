<?php
// 25/5/19 made
  $userid = "webuser";
  $passwd = "";

  $sendData = json_decode($_POST['sendData']);
  $diesStatusId = array_shift($sendData);

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

    // print_r($sendData);
    // die;
    foreach($sendData as $row){
      $sql = 
        "INSERT INTO t_dies_status_filename
           (t_dies_status_id, file_name, time_stamp)
         VALUES(:t_dies_status_id, :file_name, NOW())";
      $prepare = $dbh->prepare($sql);
      $prepare->bindValue(":t_dies_status_id", (INT)$diesStatusId, PDO::PARAM_INT);
      $prepare->bindValue(":file_name", $row, PDO::PARAM_STR);
      $prepare->execute();
    }

    echo json_encode("INSERTED");
  } catch (PDOException $e){
    $error = $e->getMessage();
    print_r($error);
  }
  $dbh = null;
?>
