<?php
header("Content-Type: application/json");

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=extrusion;charset=utf8",
        "webuser",
        ""
    );
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

/* ★ leave_at が NULL（現役スタッフ）のみ取得 */
$sql = "SELECT id, staff_name FROM m_staff WHERE leave_at IS NULL ORDER BY staff_name";
$stmt = $pdo->query($sql);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));