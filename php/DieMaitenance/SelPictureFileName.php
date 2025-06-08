<?php
  /* 25/05/21 */
  $userid = "webuser";
  $passwd = "";
  // print_r($_POST);
  
  try {
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
        SELECT 
          t_dies_status_filename.file_name
        FROM t_dies_status_filename
        WHERE t_dies_status_filename.t_dies_status_id = :die_status_id

      
      ";

      $prepare = $dbh->prepare($sql);
      // $_POST["targetId"] = 1;
      $prepare->bindValue(':die_status_id', (INT)$_POST["die_status_id"], PDO::PARAM_INT);
      $prepare->execute();
      $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($result);
  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
