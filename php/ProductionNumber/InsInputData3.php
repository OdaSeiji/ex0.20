<?php
  /* 21/09/05 */
  require_once __DIR__ . "/../db.php";
  require_once __DIR__ . "/production_number_common.php";

  try {
    $pdo->beginTransaction();

    $prepare = $pdo->prepare(
      "INSERT INTO m_production_numbers (

aging_type_id,
billet_material_id,
circumscribed_circle,
hardness,
hardness_note,
cross_section_area,
drawn_department,
packing_quantity,
production_category2_id,
production_length,
production_number,
specific_weight,
packing_column,
packing_row,
created_at

        ) VALUES (

:aging_type_id,
:billet_material_id,
:circumscribed_circle,
:hardness,
:hardness_note,
:cross_section_area,
:drawn_department,
:packing_quantity,
:production_category2_id,
:production_length,
:production_number,
:specific_weight,
:packing_column,
:packing_row,
:updated_at

        )"
    );

$prepare->bindValue(':aging_type_id', (INT)$_POST['aging_type_id'], PDO::PARAM_INT);
$prepare->bindValue(':billet_material_id', (INT)$_POST['billet_material_id'], PDO::PARAM_INT);
$prepare->bindValue(':circumscribed_circle', $_POST['circumscribed_circle'], PDO::PARAM_STR);
$prepare->bindValue(':hardness', $_POST['hardness'], PDO::PARAM_STR);
$prepare->bindValue(':hardness_note', $_POST['hardness_note'], PDO::PARAM_STR);
$prepare->bindValue(':cross_section_area', $_POST['cross_section_area'], PDO::PARAM_STR);
$prepare->bindValue(':drawn_department', (INT)$_POST['drawn_department'], PDO::PARAM_INT);
$prepare->bindValue(':packing_quantity', (INT)$_POST['packing_quantity'], PDO::PARAM_INT);
$prepare->bindValue(':production_length', $_POST['production_length'], PDO::PARAM_STR);
$prepare->bindValue(':production_number', $_POST['production_number'], PDO::PARAM_STR);
$prepare->bindValue(':specific_weight', $_POST['specific_weight'], PDO::PARAM_STR);
$prepare->bindValue(':packing_column', (INT)$_POST['packing_column'], PDO::PARAM_INT);
$prepare->bindValue(':packing_row', (INT)$_POST['packing_row'], PDO::PARAM_INT);
$prepare->bindValue(':updated_at', $_POST['updated_at'], PDO::PARAM_STR);

if($_POST['production_category2_id'] == '0')
$prepare->bindValue(':production_category2_id', Null, PDO::PARAM_STR);
else
$prepare->bindValue(':production_category2_id', $_POST['production_category2_id'], PDO::PARAM_STR);

    $prepare->execute();
    $pnId = $pdo->lastInsertId();

    $extraLengths = parseLengthOptions($_POST['lengthOptions'] ?? '');
    saveLengthOptions($pdo, $pnId, $_POST['production_length'], $extraLengths);

    $pdo->commit();
    echo json_encode("INSERTED");
  } catch (Exception $e) {
    $pdo->rollBack();
    $error = $e->getMessage();
    print_r($error);
  }
?>
