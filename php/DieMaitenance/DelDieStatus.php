<?php
  /* 25/05/25 */
  $userid = "webuser";
  $passwd = "";
  // print_r($_POST);
  $data = $_POST['dieStatudId'];
  // print_r($data);
  
  // $data = [1234, 5678];
  // print_r($data);

  // exit();
  // if (is_array($data)) {
  //   echo "$data は配列です。<br>";
  // } else {
  //   echo "$data は配列ではありません。<br>";
  // }
  // print_r(is_array($data));


  // exit();
  
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

        if(!empty($data)){
          foreach($data as $value){
            $sql = "DELETE FROM t_dies_status WHERE id = :id";
            $prepare = $dbh->prepare($sql);
            $prepare->bindValue(":id", (INT)$value, PDO::PARAM_INT);
            $prepare->execute();
          }
        }
        echo json_encode(["status" => "success"]);

        // $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
        // echo json_encode($result);

  } catch (PDOException $e) {
      $error = $e->getMessage();
      echo json_encode($error);
  }
  $dbh = null;
?>