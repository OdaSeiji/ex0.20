<?php
/* 25/05/12 */
$userid = "webuser";
$passwd = "";
// print_r($_POST);
// $_POST["die_id"] = 100;
try {
  $dbh = new PDO(
    "mysql:host=localhost; dbname=extrusion; charset=utf8",
    $userid,
    $passwd,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );
  $sql = "
        SELECT
            t1.t_die_status_id,
            date_format(date_time, '%y/%m/%d %H:%i') AS date_time,
            m_die_status.die_status,
            t1.flag
        FROM
            (
                SELECT
                    9999999 AS t_die_status_id,
                    cast(concat(t_press.press_date_at, ' ', t_press.press_start_at) AS DATETIME) AS date_time,
                    11 AS die_status_id,
                    '' as flag
                FROM
                    t_press
                WHERE
                    t_press.dies_id = :die_id_1
                UNION
                SELECT
                  t_dies_status.id AS t_die_status_id,
                  t_dies_status.do_sth_at AS date_time,
                  t_dies_status.die_status_id,
                  IF(
                    t_dies_status.file_url != 'No_image.jpg' 
                    OR EXISTS (
                      SELECT 1
                      FROM t_dies_status_filename
                      WHERE t_dies_status_filename.t_dies_status_id = t_dies_status.id
                    ),
                    'I',
                    ''
                  ) AS flag
                FROM
                  t_dies_status
                WHERE
                  t_dies_status.dies_id = :die_id_2
                ) AS t1
            LEFT JOIN
                m_die_status
            on  t1.die_status_id = m_die_status.id
        ORDER BY
            t1.date_time desc
              ";
  $prepare = $dbh->prepare($sql);
  $prepare->bindValue(":die_id_1", (int) $_POST["die_id"], PDO::PARAM_INT);
  $prepare->bindValue(":die_id_2", (int) $_POST["die_id"], PDO::PARAM_INT);
  $prepare->execute();
  $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($result);
} catch (PDOException $e) {
  $error = $e->getMessage();
  echo json_encode($error);
}
$dbh = null;
?>