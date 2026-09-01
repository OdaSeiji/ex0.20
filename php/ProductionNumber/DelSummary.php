<?php
  /* 21/04/29作成 */
  require_once __DIR__ . "/../db.php";

  try {
    $stmt = $pdo->prepare("DELETE FROM m_production_numbers WHERE id=:targetId");
    $stmt->bindValue(':targetId', (INT)$_POST["targetId"], PDO::PARAM_INT);
    $stmt->execute();

    echo(json_encode("Deleted"));
  } catch (PDOException $e){
    $error = $e->getMessage();
    print_r($error);
  }
?>
