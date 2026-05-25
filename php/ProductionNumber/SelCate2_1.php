<?php
  /* 21/04/27作成 */
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
  m_production_numbers_category2.category1_id,
  m_production_numbers_category2.id AS category2_id
FROM m_production_numbers_category2
WHERE m_production_numbers_category2.id = (
  SELECT 
    m_production_numbers.production_category2_id
  FROM m_production_numbers
  WHERE m_production_numbers.id = :targetId
)

    ");

    $prepare->bindValue(':targetId', $_POST["targetId"], PDO::PARAM_STR); 
    $prepare->execute();
    $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
  } catch (PDOException $e){
    $error = $e->getMessage();
    echo json_encode($error);
  }
  $dbh = null;
?>
