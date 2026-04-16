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

$issue_id = isset($_GET["id"]) ? $_GET["id"] : "";

if ($issue_id === "") {
    echo json_encode(["error" => "Missing issue_id"]);
    exit;
}

$sql = "
SELECT 
    i.id,
    i.issue_title,
    d.die_number
FROM t_die_issue i
JOIN m_dies d ON i.die_id = d.id
WHERE i.id = :issue_id
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(["error" => "Issue not found"]);
        exit;
    }

    echo json_encode($row);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}