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
            t1.dies_id,
            t1.id,
            m_dies.die_number,
            date_format(t1.do_sth_at, '%m/%d') AS on_rack
        FROM
            t_dies_status AS t1
            LEFT JOIN
                m_dies
            ON  t1.dies_id = m_dies.id
        WHERE
            t1.do_sth_at = (
                SELECT
                    MAX(t2.do_sth_at) AS do_sth_at
                FROM
                    t_dies_status AS t2
                WHERE
                    t1.dies_id = t2.dies_id
                    AND 
                    t2.die_status_id = 10
            )
        ORDER BY
            date_format(t1.do_sth_at, '%y-%m-%d') DESC,
            die_number
        LIMIT 50

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
