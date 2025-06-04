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
          t1.dies_id,
          m_dies.die_number,
          date_format(t1.do_sth_at, '%y/%m/%d') AS DATE,
          m_die_status.die_status
        FROM t_dies_status AS t1
        LEFT JOIN m_dies
          ON t1.dies_id = m_dies.id	
        LEFT JOIN m_die_status
          ON t1.die_status_id = m_die_status.id
        WHERE t1.do_sth_at =
          (
            SELECT 
              MAX(t2.do_sth_at)
            FROM t_dies_status AS t2
            WHERE t1.dies_id = t2.dies_id
            GROUP BY t2.dies_id
          )
        ORDER BY m_dies.die_number
      ";

      $prepare = $dbh->prepare($sql);
      // $_POST["targetId"] = 1;
      // $prepare->bindValue(':staff_order', (INT)$_POST["staffOrder"], PDO::PARAM_INT);
      $prepare->execute();
      $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($result);
  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
