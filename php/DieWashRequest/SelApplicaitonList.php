<?php
  /* 25/10/31 */
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
          t_application.id,
          m_dies.die_number,
          m_staff.staff_name,
          DATE_FORMAT(t_application.application_datetime, '%m/%d') as date,
          t_application.reason,
          t_application.approval_result
        FROM t_application
        left join m_dies
          on t_application.die_id = m_dies.id
        left join m_staff
          ON t_application.staff_id = m_staff.id
        order by t_application.application_datetime desc
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
