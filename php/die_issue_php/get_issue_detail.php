<?php
header("Content-Type: application/json");

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=extrusion;charset=utf8",
        "webuser",
        ""
    );
} catch (PDOException $e) {
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

$issue_id = $_GET["id"] ?? "";

if ($issue_id === "") {
    echo json_encode(["error" => "Missing id"]);
    exit;
}

/* --- Issue 本体情報 --- */
$sql = "
SELECT 
    i.*,
    d.die_number,
    s1.staff_name AS reported_by_name,
    s2.staff_name AS approved_by_name,
    s3.staff_name AS completed_by_name
FROM t_die_issue i
LEFT JOIN m_dies d ON i.die_id = d.id
LEFT JOIN m_staff s1 ON i.reported_by = s1.id
LEFT JOIN m_staff s2 ON i.approved_by = s2.id
LEFT JOIN m_staff s3 ON i.completed_by = s3.id
WHERE i.id = :id
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $issue_id, PDO::PARAM_INT);
$stmt->execute();

$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode(["error" => "Issue not found"]);
    exit;
}

/* --- 添付ファイル一覧を取得 --- */
$sql2 = "
SELECT 
    id,
    original_name,
    saved_name
FROM t_die_issue_attachment
WHERE issue_id = :issue_id
ORDER BY id ASC
";

$stmt2 = $pdo->prepare($sql2);
$stmt2->bindValue(":issue_id", $issue_id, PDO::PARAM_INT);
$stmt2->execute();

$attachments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

/* --- JSON に追加 --- */
$data["before_photos"] = $attachments;

echo json_encode($data);