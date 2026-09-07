<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$pressingTypes = $pdo->query("SELECT id, pressing_type, remarks_jp FROM m_pressing_type ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$nbn = $pdo->query("SELECT id, nbn FROM m_nbn ORDER BY nbn")->fetchAll(PDO::FETCH_ASSOC);
$staff = $pdo->query("SELECT id, staff_name FROM m_staff WHERE leave_at IS NULL ORDER BY staff_name")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "pressingTypes" => $pressingTypes,
    "nbn"           => $nbn,
    "staff"         => $staff,
], JSON_UNESCAPED_UNICODE);
