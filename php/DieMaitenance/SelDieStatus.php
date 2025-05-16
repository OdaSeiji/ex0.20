<?php
  /* 25/05/12 */
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
          t_dies_status.id,
          m_dies.die_number,
          date_format(t_dies_status.do_sth_at, '%y/%m/%d') AS date,
          m_die_status.die_status
        FROM t_dies_status
        LEFT JOIN m_dies
          ON t_dies_status.dies_id = m_dies.id
        LEFT JOIN m_die_status
          ON t_dies_status.die_status_id = m_die_status.id
        where t_dies_status.dies_id = :dies_id
        ORDER BY t_dies_status.do_sth_at desc
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
