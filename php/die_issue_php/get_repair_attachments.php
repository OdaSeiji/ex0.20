<?php
header("Content-Type: application/json");

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=extrusion;charset=utf8",
        "webuser",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

$record_id = isset($_GET["record_id"]) ? $_GET["record_id"] : "";

if ($record_id === "") {
    echo json_encode(["error" => "Missing record_id"]);
    exit;
}

$sql = "
SELECT 
    id,
    file_name,
    description,
    attached_at
FROM t_die_clinical_record_attachment
WHERE clinical_record_id = :record_id
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":record_id", $record_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}