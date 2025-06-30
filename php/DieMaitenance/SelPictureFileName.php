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
            WITH all_data_table AS(
            SELECT
                t_dies_status_filename.file_name
            FROM
                t_dies_status_filename
            WHERE
                t_dies_status_filename.t_dies_status_id = :die_status_id_1
            UNION
            SELECT
                t_dies_status.file_url AS file_name
            FROM
                t_dies_status
            WHERE
                t_dies_status.id = :die_status_id_2
            )
            SELECT
            *
            FROM
            all_data_table
            WHERE
            all_data_table.file_name IS NOT NULL
        ";

      $prepare = $dbh->prepare($sql);
      // $_POST["targetId"] = 1;
      $prepare->bindValue(':die_status_id_1', (INT)$_POST["die_status_id"], PDO::PARAM_INT);
      $prepare->bindValue(':die_status_id_2', (INT)$_POST["die_status_id"], PDO::PARAM_INT);
      $prepare->execute();
      $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($result);
  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
