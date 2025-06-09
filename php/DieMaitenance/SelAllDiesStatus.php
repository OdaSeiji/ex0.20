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

WITH MostRecentPressDateByDies AS(
    SELECT
        t_press.dies_id,
        m_pressing_type.pressing_type,
        date_format(MAX(t_press.press_date_at), '%y/%m/%d') AS press_date
    FROM
        t_press
        LEFT JOIN
            m_pressing_type
        ON  t_press.pressing_type_id = m_pressing_type.id
    GROUP BY
        t_press.dies_id
),
MostRecentDieMentainanceByDies AS(
    SELECT
        t1.id,
        t1.dies_id,
        date_format(t1.do_sth_at, '%y/%m/%d') AS status_date,
        t1.die_status_id,
        m_die_status.die_status
    FROM
        t_dies_status AS t1
        LEFT JOIN
            m_die_status
        ON  t1.die_status_id = m_die_status.id
    WHERE
        t1.do_sth_at = (
            SELECT
                MAX(t2.do_sth_at)
            FROM
                t_dies_status AS t2
            WHERE
                t1.dies_id = t2.dies_id
            GROUP BY
                t2.dies_id
        )
)
SELECT
    m_dies.id,
    m_dies.die_number,
    MostRecentPressDateByDies.pressing_type,
    MostRecentPressDateByDies.press_date,
    MostRecentDieMentainanceByDies.die_status,
    MostRecentDieMentainanceByDies.status_date,
    MostRecentDieMentainanceByDies.id as status_id
FROM
    m_dies
    LEFT JOIN
        MostRecentPressDateByDies
    ON  MostRecentPressDateByDies.dies_id = m_dies.id
    LEFT JOIN
        MostRecentDieMentainanceByDies
    ON  MostRecentDieMentainanceByDies.dies_id = m_dies.id
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
