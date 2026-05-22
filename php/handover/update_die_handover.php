<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$rows = json_decode(file_get_contents("php://input"), true);

if (!$rows || !is_array($rows)) {
    echo json_encode(["status" => "error", "message" => "invalid input"]);
    exit;
}

$sql = "
    UPDATE t_die_handover SET
        press_condition_document_completion_at      = ?,
        qa_dimension_inspection_completed_at        = ?,
        qa_dimension_inspection_document_number     = ?,
        dimension_inspection_sample_sent_at         = ?,
        arrived_at                                  = ?,
        capitalization_date                         = ?,
        memo                                        = ?
    WHERE id = ?
";

$stmt = $pdo->prepare($sql);
$updated = 0;

foreach ($rows as $row) {
    $stmt->execute([
        $row["press_condition_document_completion_at"]       ?: null,
        $row["qa_dimension_inspection_completed_at"]         ?: null,
        $row["qa_dimension_inspection_document_number"]      ?: null,
        $row["dimension_inspection_sample_sent_at"]          ?: null,
        $row["arrived_at"]                                   ?: null,
        $row["capitalization_date"]                          ?: null,
        $row["memo"]                                         ?: null,
        $row["id"],
    ]);
    $updated++;
}

echo json_encode(["status" => "ok", "updated" => $updated]);
