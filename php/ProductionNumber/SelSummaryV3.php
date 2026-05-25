<?php
  /* 23/03/26作成 */
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
  m_production_numbers.id,
  SUBSTRING(IFNULL(t1.category1_jp, ''),1, 8) AS category1_jp,
  SUBSTRING(IFNULL(t1.category2_jp, ''),1, 8) AS category2_jp,
  m_production_numbers.production_number,
  IFNULL(m_production_numbers.drawn_department, '') AS drawn_department,
  IFNULL(m_billet_material.billet_material, '') AS billet_material, 
  IFNULL(m_aging_type.aging_type, '') AS aging_type,
  FORMAT(m_production_numbers.cross_section_area, 1) AS cross_section_area,
  FORMAT(m_production_numbers.specific_weight, 2) AS specific_weight,
  FORMAT(m_production_numbers.production_length, 3) AS production_length,
  m_production_numbers.packing_quantity,
  IFNULL(m_production_numbers.packing_column, '') AS packing_column,
  IFNULL(m_production_numbers.packing_row, '') AS packing_row,
  IFNULL(m_production_numbers.hardness, '') AS hardness,
  IFNULL(m_production_numbers.hardness_note, '') AS hardness_note,
  DATE_FORMAT(m_production_numbers.created_at,'%y-%m-%d')  as created_at
FROM m_production_numbers
LEFT JOIN m_billet_material ON m_production_numbers.billet_material_id = m_billet_material.id
LEFT JOIN m_aging_type ON m_production_numbers.aging_type_id = m_aging_type.id
LEFT JOIN 
(
SELECT 
  m_production_numbers_category2.id AS category2_id,
  m_production_numbers_category1.name_jp AS category1_jp,
  m_production_numbers_category2.name_jp AS category2_jp
FROM m_production_numbers_category2
LEFT JOIN m_production_numbers_category1 
ON m_production_numbers_category2.category1_id = m_production_numbers_category1.id
) AS t1
ON m_production_numbers.production_category2_id = t1.category2_id
ORDER BY
  t1.category1_jp,
  t1.category2_jp,
  m_production_numbers.production_number


   ");

      // $prepare->bindValue(':targetId',(INT)$_POST["targetId"],PDO::PARAM_INT);


    $prepare->execute();
    $result = $prepare->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
  } catch (PDOException $e){
    $error = $e->getMessage();
    echo json_encode($error);
  }
  $dbh = null;
?>
