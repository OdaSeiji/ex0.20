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

        WITH experience_staff AS(
            SELECT
                t_dies_status.staff_id,
                COUNT(*) AS cnt
            FROM
                t_dies_status
            WHERE
                t_dies_status.die_status_id IN(7, 9)
            AND t_dies_status.do_sth_at >= DATE_SUB(CURDATE(), INTERVAL 4 MONTH)
            GROUP BY
                t_dies_status.staff_id
        )
        SELECT
            m_staff.id,
            m_staff.staff_name,
            ifnull(experience_staff.cnt, 0) AS cnt
        FROM
            m_staff
            LEFT JOIN
                experience_staff
            ON  experience_staff.staff_id = m_staff.id
        ORDER BY
            cnt DESC,
            m_staff.id      

      ";

      $prepare = $dbh->prepare($sql);
      // $_POST["targetId"] = 1;
      $prepare->bindValue(':staff_order', (INT)$_POST["staffOrder"], PDO::PARAM_INT);
      $prepare->execute();
      $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($result);
  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
