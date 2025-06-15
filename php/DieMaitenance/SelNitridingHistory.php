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

      $sql = "set @specific_grabity_of_alminium = 2.70";
      $dbh->exec($sql);
      $sql = "set @pi = 3.141459";
      $dbh->exec($sql);
      $sql = "set @inch = 25.4";
      $dbh->exec($sql);

      $sql = "

              with nitriding_term as (
        select 
          t_n_1.nitriding_date_at as n_start_date,
          min(t_n_2.nitriding_date_at) as n_end_date
        from t_nitriding as t_n_1
        left join t_nitriding as t_n_2
          on t_n_1.nitriding_date_at < t_n_2.nitriding_date_at
        where 
          t_n_1.dies_id = @die_id
          and
          t_n_2.dies_id = @die_id
        group by t_n_1.nitriding_date_at
        ), dies_id_and_production_weight as (
        select 
          m_dies.id as dies_id,
          m_dies.hole,
          m_production_numbers.specific_weight
        from m_dies
        left join m_production_numbers
          on m_dies.production_number_id = m_production_numbers.id
        )
        select 
          'dummy',
          nitriding_term.n_end_date,
          format(round(SUM((@pi * POWER(t_press.billet_size * @inch / 2, 2) 
                * t_press.billet_length * 0.001 * @specific_grabity_of_alminium
                * t_press.actual_billet_quantities / 1000)
                / specific_weight /1000 / hole), 2), 2) as length_after_nitriding
        from nitriding_term
        left join t_press
          on 
          nitriding_term.n_start_date < t_press.press_date_at
          AND
          nitriding_term.n_end_date > t_press.press_date_at
        left join dies_id_and_production_weight
          on t_press.dies_id = dies_id_and_production_weight.dies_id
        where t_press.dies_id = @die_id
        group by   nitriding_term.n_end_date


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
