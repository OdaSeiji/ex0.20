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

      $dbh->query("SET @washing = 4");
      $dbh->query("SET @ng_surface = 31");
      $dbh->query("SET @ng_dimension = 32");
      
      $prepare = $dbh->prepare("

WITH latest_press_date_by_die_id AS(
  SELECT
    t1.id as press_id,
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
      AND t2.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    )
  and t1.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
  ORDER BY
    t1.dies_id
), latest_washing_date_by_dies_id as(
  select
    t1.dies_id as dies_id,
    t1.do_sth_at as washing_date
  from
    t_dies_status as t1
  where
    t1.die_status_id = @washing
  AND t1.do_sth_at = (
      select
        max(t2.do_sth_at)
      from
        t_dies_status as t2
      where
        t2.die_status_id = @washing
      and t2.dies_id = t1.dies_id
    )
), latest_washing_or_racking_date_by_die_id as (
  SELECT
    t1.id as t_dies_status_id,
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
      AND t2.die_status_id IN(@washing, @racking)
      and t2.do_sth_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    )
  and t1.do_sth_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
), after_press_ng_dies_id as (
select 
  t_dies_status.dies_id,
  t_dies_status.die_status_id
from t_dies_status
left join latest_press_date_by_die_id
  on t_dies_status.dies_id = latest_press_date_by_die_id.dies_id
where t_dies_status.die_status_id IN (@ng_surface, @ng_dimension)
  AND
  t_dies_status.do_sth_at > latest_press_date_by_die_id.press_date_at
),  after_press_dies as (
  select
    latest_press_date_by_die_id.dies_id,
    latest_press_date_by_die_id.press_date_at,
    latest_press_date_by_die_id.pressing_type_id
    
  from latest_press_date_by_die_id
  left join latest_washing_or_racking_date_by_die_id
    on latest_press_date_by_die_id.dies_id = latest_washing_or_racking_date_by_die_id.dies_id
  where latest_press_date_by_die_id.press_date_at > latest_washing_or_racking_date_by_die_id.do_sth_at
), latest_washing_by_die_id as (
  select
    t1.dies_id,
    t1.do_sth_at as washing_date,
    row_number() over (partition by t1.dies_id order by t1.do_sth_at desc) as rn
  from
    t_dies_status t1
  where
    t1.die_status_id = @washing
), press_count_after_wash as (
  select 
    t3.dies_id,
    count(*) as press_count_no_wash
  from t_press as t3
  left join latest_washing_date_by_dies_id latest
    on t3.dies_id = latest.dies_id
  where latest.washing_date < t3.press_date_at + interval time_to_sec(t3.press_start_at) second
  group by t3.dies_id
)
select 
  after_press_dies.dies_id,
  DATE_FORMAT(after_press_dies.press_date_at,'%m/%d') as press_date_at,
  m_dies.die_number,
  m_pressing_type.pressing_type,
  press_count_after_wash.press_count_no_wash,
  if(after_press_ng_dies_id.die_status_id is not null,'NG','OK'),
      case 
    	when after_press_dies.pressing_type_id = 1
  			then if(after_press_ng_dies_id.die_status_id IS NOT NULL, 'Wash', 'Rack')
    	when m_pressing_type.id = 2
  			then if((after_press_ng_dies_id.die_status_id IS not NULL) or (press_count_no_wash > 1), 'Wash', 'Rack')
    	when m_pressing_type.id = 3
			  then if((after_press_ng_dies_id.die_status_id IS not NULL) or (press_count_no_wash > 1), 'Wash', 'Rack')
      end as action,
    DATE_FORMAT(after_press_dies.press_date_at, '%y%m%d') as yymmdd
from after_press_dies
left join press_count_after_wash
  on after_press_dies.dies_id = press_count_after_wash.dies_id
left join after_press_ng_dies_id
  on after_press_dies.dies_id = after_press_ng_dies_id.dies_id
left join m_dies
  on after_press_dies.dies_id = m_dies.id
left join m_pressing_type
  on after_press_dies.pressing_type_id = m_pressing_type.id
order by date_format(after_press_dies.press_date_at, '%y%m%d') desc, m_dies.die_number asc


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
