<?php
  /* 25/02/27 made */
  $userid = "webuser";
  $passwd = "";
  // print_r($_POST);
  
  try {
    $dbh = new PDO(
      'mysql:host=localhost; 
      dbname=billet_casting; 
      charset=utf8',
      $userid,
      $passwd,
      array(
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => false
      )
    );

    $prepare = $dbh->prepare("
      SELECT 
        t_casting.id,
        t_casting.code,
        date_format(t_casting.product_date, '%y-%m-%d')
      FROM t_casting
      WHERE t_casting.code != ''
      ORDER BY id desc
      LIMIT 200;
    ");
      // $_POST["targetId"] = 1;
#      $prepare->bindValue(':targetId', (INT)$_POST["targetId"], PDO::PARAM_INT);
      $prepare->execute();
      $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($result);
  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
