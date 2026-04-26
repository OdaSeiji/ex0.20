<?php
require_once "./../db.php";

try {
    // leave_at が NULL の現役スタッフのみ取得
    $sql = "SELECT id, staff_name 
            FROM m_staff 
            WHERE leave_at IS NULL
            ORDER BY staff_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);

} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => "DB error: " . $e->getMessage()
    ]);
}
