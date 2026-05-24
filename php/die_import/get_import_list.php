<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

$flag = $_GET["flag"] ?? "pending";
if ($flag === "pending") {
    $where = "WHERE t.import_flag = 0";
} elseif ($flag === "transferred") {
    $where = "WHERE t.import_flag = 1";
} else {
    $where = "";
}

$sql = "
    SELECT
        t.id,
        t.budget_id,
        t.die_number,
        t.production_number_raw,
        t.production_number_id,
        COALESCE(p.production_number, '') AS production_number,
        t.die_diamater_id,
        t.billet_size_id,
        t.bolstar_id,
        t.import_flag,
        t.import_error
    FROM t_dies_import_tmp t
    LEFT JOIN m_production_numbers p ON t.production_number_id = p.id
    {$where}
    ORDER BY t.id ASC
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
