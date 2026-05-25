<?php
  /* 21/04/26作成 */
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
        m_production_numbers_category2.id,
        SUBSTRING(m_production_numbers_category2.name_jp, 1, 9) AS name_jp,
        IFNULL(t1.count, 0) as count
      FROM 
      m_production_numbers_category2
      LEFT JOIN 
        (
          SELECT 
            m_production_numbers.production_category2_id, COUNT(*) AS count
          FROM 
            m_production_numbers
          WHERE 
            m_production_numbers.production_category2_id IS NOT NULL
          GROUP BY 
            m_production_numbers.production_category2_id
        ) AS t1 ON m_production_numbers_category2.id = t1.production_category2_id
      WHERE m_production_numbers_category2.category1_id = :targetId

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
