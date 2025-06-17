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

      $sql = "set @die_id = :die_id";
      $prepare = $dbh->prepare($sql);
      $prepare->bindValue(':die_id', (INT)$_POST["dieId"], PDO::PARAM_INT);
      $prepare->execute();

      // $sql = "set @specific_grabity_of_alminium = 2.70";
      // $dbh->exec($sql);
      // $sql = "set @pi = 3.141459";
      // $dbh->exec($sql);
      // $sql = "set @inch = 25.4";
      // $dbh->exec($sql);

      $sql = "
SELECT
  t_nitriding.id,
  m_dies.die_number,
  t_nitriding.nitriding_date_at
from t_nitriding
left join m_dies
  on t_nitriding.dies_id = m_dies.id
order by t_nitriding.nitriding_date_at desc, m_dies.die_number

      ";

      $prepare = $dbh->prepare($sql);
      $prepare->execute();
      $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($result);
  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
