<?php
header("Content-Type: application/json");

// デバッグログ
file_put_contents("debug_repair_list.txt", "\n===== NEW REQUEST =====\n", FILE_APPEND);
file_put_contents("debug_repair_list.txt", "GET:\n" . print_r($_GET, true) . "\n", FILE_APPEND);

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=extrusion;charset=utf8",
        "webuser",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents("debug_repair_list.txt", "DB ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

$issue_id = isset($_GET["issue_id"]) ? $_GET["issue_id"] : "";

$sql = "
SELECT 
    r.id,
    r.record_date,
    r.memo,
    s.staff_name AS repaired_by_name,
    (
        SELECT COUNT(*) 
        FROM t_die_clinical_record_attachment a
        WHERE a.clinical_record_id = r.id
    ) AS attachment_count
FROM t_die_clinical_record r
LEFT JOIN m_staff s ON r.staff_id = s.id
WHERE r.issue_id = :issue_id
ORDER BY r.record_date DESC
";

file_put_contents("debug_repair_list.txt", "SQL:\n$sql\n", FILE_APPEND);

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    file_put_contents("debug_repair_list.txt", "RESULT:\n" . print_r($rows, true) . "\n", FILE_APPEND);

    echo json_encode($rows);

} catch (PDOException $e) {
    file_put_contents("debug_repair_list.txt", "SQL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(["error" => $e->getMessage()]);
}