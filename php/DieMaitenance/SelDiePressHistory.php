<?php
  /* 25/05/18 */
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

      $prepare = $dbh->prepare("
        SELECT 
          t_press.id,
          m_dies.die_number,
          date_format(t_press.press_date_at, '%y/%m/%d') AS press_date
        FROM t_press
        LEFT JOIN m_dies
          ON t_press.dies_id = m_dies.id
        WHERE t_press.dies_id = :dies_id
        ORDER BY press_date_at desc
      ");
      // $_POST["targetId"] = 1;
      $prepare->bindValue(':dies_id', (INT)$_POST["dies_id"], PDO::PARAM_INT);
      $prepare->execute();
      $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($result);
  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
