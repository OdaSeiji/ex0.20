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

        WITH dies_last_pressed_date_query AS (
          SELECT 
            t1.id,
            t1.dies_id,
            concat(t1.press_date_at, ' ', t1.press_start_at) AS press_date_at
          FROM 
            t_press AS t1
          WHERE 
            concat(t1.press_date_at, ' ', t1.press_start_at) = (
              SELECT MAX(CONCAT(t2.press_date_at, ' ', t2.press_start_at))
              FROM t_press AS t2
              WHERE t1.dies_id = t2.dies_id
            )
          ORDER BY t1.dies_id
          ) , dies_last_status_date_query AS (
          SELECT 
            t1.id,
            t1.dies_id,
            t1.do_sth_at,
            t1.die_status_id
          FROM t_dies_status AS t1
          WHERE t1.do_sth_at = (
            SELECT MAX(t2.do_sth_at)
            FROM t_dies_status AS t2
            WHERE t1.dies_id = t2.dies_id
            )
          ) 
        SELECT 
          dies_last_pressed_date_query.dies_id,
          date_format(dies_last_pressed_date_query.press_date_at, '%y/%m/%d') AS press_date_at,
          m_dies.die_number
        FROM dies_last_pressed_date_query
        INNER JOIN dies_last_status_date_query
          ON dies_last_pressed_date_query.dies_id = dies_last_status_date_query.dies_id
        LEFT JOIN m_dies
          ON dies_last_pressed_date_query.dies_id = m_dies.id
        WHERE dies_last_pressed_date_query.press_date_at > dies_last_status_date_query.do_sth_at
        #ORDER BY dies_last_pressed_date_query.dies_id
        ORDER BY press_date_at DESC

      ");
      // $_POST["targetId"] = 1;
      // $prepare->bindValue(':machine', (INT)$_POST["machine"], PDO::PARAM_INT);
      $prepare->execute();
      $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($result);
  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
