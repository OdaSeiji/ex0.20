<?php
  /* 25/06/14 */
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

      $sql = "set @washing_die_status = 4";
      $prepare = $dbh->prepare($sql);
      $prepare->execute();

      $sql = "set @specific_grabity_of_alminium = 2.70";
      $prepare = $dbh->prepare($sql);
      $prepare->execute();

      $sql = "set @pi = 3.141459";
      $prepare = $dbh->prepare($sql);
      $prepare->execute();
      
      $sql = "set @inch = 25.4";
      $prepare = $dbh->prepare($sql);
      $prepare->execute();

$sql = "
with latest_nitriding_date_by_dies_id as (
  SELECT
    m_dies.id as dies_id,
    IFNULL(t10.nitriding_date_at, DATE_FORMAT('2021-1-1', '%y-%m-%d')) as nitriding_date_at
  from m_dies
  left join (
    select 
      t10.dies_id,
      t10.nitriding_date_at
    from t_nitriding as t10
    where t10.nitriding_date_at = (
      select
        max(t2.nitriding_date_at)
      from t_nitriding as t2
      where t2.dies_id = t10.dies_id
      group by t2.dies_id
      )
    ) as t10
  on m_dies.id = t10.dies_id
), profile_length_after_nitriding_by_dies_id as (
  select 
    t_press.dies_id,
    cast(round(SUM(((@pi * POWER(t_press.billet_size * @inch / 2, 2) 
      * t_press.billet_length * 0.001 * @specific_grabity_of_alminium
      * t_press.actual_billet_quantities / 1000) 
      / specific_weight /1000 / m_dies.hole)), 1) as char) as length_km_after_nitiriding
  #  count(t_press.id) as press_count_after_nitriding
  from latest_nitriding_date_by_dies_id
  left join t_press
    on t_press.dies_id = latest_nitriding_date_by_dies_id.dies_id
  left join m_dies
    on latest_nitriding_date_by_dies_id.dies_id = m_dies.id
  left join m_production_numbers
    on m_dies.production_number_id = m_production_numbers.id
  where t_press.press_date_at > latest_nitriding_date_by_dies_id.nitriding_date_at
  group by t_press.dies_id
), washing_count_after_nitriding_by_dies_id as (
  select 
    t_dies_status.dies_id,
    count(t_dies_status.dies_id) as washing_count_after_nitriding
  from t_dies_status
  left join latest_nitriding_date_by_dies_id
    on t_dies_status.dies_id = latest_nitriding_date_by_dies_id.dies_id
  where 
    t_dies_status.die_status_id = @washing_die_status
    and 
    t_dies_status.do_sth_at > latest_nitriding_date_by_dies_id.nitriding_date_at
  group by t_dies_status.dies_id
), dies_id_and_production_weight as (
select 
  m_dies.id as dies_id,
  m_dies.hole,
  m_production_numbers.specific_weight
from m_dies
left join m_production_numbers
  on m_dies.production_number_id = m_production_numbers.id
), total_profile_length_by_dies_id as (
  select 
    t_press.dies_id,
    cast(round(SUM(((@pi * POWER(t_press.billet_size * @inch / 2, 2) 
        * t_press.billet_length * 0.001 * @specific_grabity_of_alminium
        * t_press.actual_billet_quantities / 1000) 
        / specific_weight /1000 / hole)), 1) as char) as total_profile_length
  from t_press
  left join dies_id_and_production_weight
    on t_press.dies_id = dies_id_and_production_weight.dies_id
  group by t_press.dies_id
), total_washing_count_by_dies_id as (
  select 
    t_dies_status.dies_id,
    count(*) as count
  from t_dies_status
  where t_dies_status.die_status_id = 4
  group by t_dies_status.dies_id
)
select 
  m_dies.id,
  m_dies.die_number,
  ifnull(profile_length_after_nitriding_by_dies_id.length_km_after_nitiriding, 0)
    as profile_length_after_nitriding,
  ifnull(washing_count_after_nitriding_by_dies_id.washing_count_after_nitriding, 0)
    as washing_count_after_nitriding,
  ifnull(total_profile_length_by_dies_id.total_profile_length, 0) as total_profile_length,
  ifnull(total_washing_count_by_dies_id.count, 0) as total_washing_count,
  m_dies_diamater.die_diamater
from m_dies
left join profile_length_after_nitriding_by_dies_id
  on m_dies.id = profile_length_after_nitriding_by_dies_id.dies_id
left join washing_count_after_nitriding_by_dies_id
  on washing_count_after_nitriding_by_dies_id.dies_id = m_dies.id
left join total_profile_length_by_dies_id
  on total_profile_length_by_dies_id.dies_id = m_dies.id
left JOIN total_washing_count_by_dies_id
  on total_washing_count_by_dies_id.dies_id = m_dies.id
left join m_dies_diamater
  on m_dies.die_diamater_id = m_dies_diamater.id
order by profile_length_after_nitriding_by_dies_id.length_km_after_nitiriding desc

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
