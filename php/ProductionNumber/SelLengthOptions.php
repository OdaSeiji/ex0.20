<?php
  require_once __DIR__ . "/../db.php";
  header("Content-Type: application/json; charset=UTF-8");

  try {
    $targetId = (int)($_POST['targetId'] ?? 0);
    if ($targetId === 0) {
      echo json_encode([]);
      exit;
    }

    $stmt = $pdo->prepare("
      SELECT id, length
      FROM m_production_number_lengths
      WHERE production_number_id = :targetId AND is_default = 0
      ORDER BY length
    ");
    $stmt->execute([":targetId" => $targetId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  } catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
  }
?>
