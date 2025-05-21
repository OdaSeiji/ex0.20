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

        WITH dies_last_pressed_date_query AS(
            SELECT
                t1.id,
                t1.dies_id,
                t1.pressing_type_id,
                t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND AS press_date_at
            FROM
                t_press AS t1
            WHERE
                t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND = (
                    SELECT
                        MAX(t2.press_date_at + INTERVAL TIME_TO_SEC(t2.press_start_at) SECOND)
                    FROM
                        t_press AS t2
                    WHERE
                        t1.dies_id = t2.dies_id
                )
            ORDER BY
                t1.dies_id
        ),
        dies_last_status_date_query AS(
            SELECT
                t1.id,
                t1.dies_id,
                t1.do_sth_at,
                t1.die_status_id
            FROM
                t_dies_status AS t1
            WHERE
                t1.do_sth_at = (
                    SELECT
                        MAX(t2.do_sth_at)
                    FROM
                        t_dies_status AS t2
                    WHERE
                        t1.dies_id = t2.dies_id
                    AND t2.die_status_id IN(4, 10)
                )
        ),
        dies_no_wash_press_time_query AS(
            SELECT
                t3.dies_id,
                COUNT(*) as no_wash_press
            FROM
                t_press AS t3
            WHERE
                t3.press_date_at + INTERVAL TIME_TO_SEC(t3.press_start_at) SECOND > (
                    SELECT
                        t1.do_sth_at
                    FROM
                        t_dies_status AS t1
                    WHERE
                        t3.dies_id = t1.dies_id
                    and t1.do_sth_at = (
                            SELECT
                                MAX(t2.do_sth_at)
                            FROM
                                t_dies_status AS t2
                            WHERE
                                t1.dies_id = t2.dies_id
                            AND t2.die_status_id = 4
                        )
                )
            GROUP BY
                t3.dies_id
        ),
        dies_id_last_diemark_ng_query AS(
            SELECT
                t1.dies_id,
                t1.do_sth_at
            FROM
                t_dies_status AS t1
                LEFT JOIN
                    dies_last_pressed_date_query
                ON  dies_last_pressed_date_query.dies_id = t1.dies_id
            WHERE
                t1.do_sth_at = (
                    SELECT
                        MAX(t2.do_sth_at)
                    FROM
                        t_dies_status AS t2
                    WHERE
                        t1.dies_id = t2.dies_id
                    and t2.die_status_id IN (31, 32)
                )
            AND t1.do_sth_at > dies_last_pressed_date_query.press_date_at
        )
        SELECT
            dies_last_pressed_date_query.dies_id,
            date_format(dies_last_pressed_date_query.press_date_at, '%y/%m/%d') AS press_date_at,
            m_dies.die_number,
            m_pressing_type.pressing_type,
            ifnull(dies_no_wash_press_time_query.no_wash_press, 0) AS no_wash_press,
            # dies_id_last_diemark_ng_query.dies_id,
            if(dies_id_last_diemark_ng_query.dies_id IS NULL, '', 'NG') AS die_mark
        FROM
            dies_last_pressed_date_query
            INNER JOIN
                dies_last_status_date_query
            ON  dies_last_pressed_date_query.dies_id = dies_last_status_date_query.dies_id
            left JOIN
                dies_no_wash_press_time_query
            ON  dies_no_wash_press_time_query.dies_id = dies_last_status_date_query.dies_id
            LEFT JOIN
                m_dies
            ON  dies_last_pressed_date_query.dies_id = m_dies.id
            LEFT JOIN
                m_pressing_type
            ON  dies_last_pressed_date_query.pressing_type_id = m_pressing_type.id
            LEFT join
                dies_id_last_diemark_ng_query
            ON  dies_last_pressed_date_query.dies_id = dies_id_last_diemark_ng_query.dies_id
        WHERE
            dies_last_pressed_date_query.press_date_at > dies_last_status_date_query.do_sth_at
        AND dies_last_status_date_query.die_status_id != 8
        ORDER BY
            press_date_at DESC,
            die_number

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
