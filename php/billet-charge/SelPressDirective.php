<?php
  /* 25/02/02 */
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
        WITH tb_01 AS (
          SELECT *
          FROM t_press
          ORDER BY t_press.id desc
          LIMIT 200)
        SELECT 
          t_press_directive.id,
          DATE_FORMAT(t_press_directive.plan_date_at, '%y-%m-%d') AS 'plan_date_at',
          m_dies.die_number,
          m_pressing_type.pressing_type,
          t_press_directive.billet_input_quantity AS 'plan-billet-qty',
          tb_01.actual_billet_quantities AS 'actual-billet-qty',
          t_press_directive.billet_size,
          t_press_directive.billet_length,
          t_press_directive.press_machine
        FROM t_press_directive
        LEFT JOIN m_dies
          ON t_press_directive.dies_id = m_dies.id
        LEFT JOIN m_pressing_type
          ON t_press_directive.pressing_type_id = m_pressing_type.id
        LEFT JOIN tb_01
          ON tb_01.press_directive_id = t_press_directive.id
        ORDER BY id DESC
        LIMIT 50
      ");
      // $_POST["targetId"] = 1;
      // $prepare->bindValue(':targetId', (INT)$_POST["targetId"], PDO::PARAM_INT);
      $prepare->execute();
      $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($result);
  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
