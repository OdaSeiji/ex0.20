<?php
  /* 13th Apr 2023 made */
  $userid = "webuser";
  $passwd = "";
  // print_r($_POST);
  
  try{
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

      SELECT 
        t1.id,
        t1.name_jp,
        t10.cnt
      FROM m_production_numbers_category2 AS t1
      LEFT JOIN 
        (
          SELECT 
            m_production_numbers.production_category2_id,
            count(m_production_numbers.id) AS cnt
          FROM 
            m_production_numbers
          GROUP BY 
            m_production_numbers.production_category2_id	
        ) AS t10 ON  t1.id = t10.production_category2_id
      WHERE t1.category1_id = 
        (
        SELECT 
          m_production_numbers_category2.category1_id
        FROM m_production_numbers_category2
        WHERE m_production_numbers_category2.id = 
          (
            SELECT 
              m_production_numbers.production_category2_id
            FROM m_production_numbers
            WHERE m_production_numbers.id = :targetId
          )
        )


   ");

      $prepare->bindValue(':targetId',(INT)$_POST["targetId"],PDO::PARAM_INT);


    $prepare->execute();
    $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
  } catch (PDOException $e){
    $error = $e->getMessage();
    echo json_encode($error);
  }
  $dbh = null;
?>
