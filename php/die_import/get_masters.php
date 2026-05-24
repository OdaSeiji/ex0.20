<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

echo json_encode([
    "production_numbers" => $pdo->query("SELECT id, production_number FROM m_production_numbers ORDER BY production_number")->fetchAll(PDO::FETCH_ASSOC),
    "diameters"          => $pdo->query("SELECT id, die_diamater FROM m_dies_diamater ORDER BY die_diamater")->fetchAll(PDO::FETCH_ASSOC),
    "billet_sizes"       => $pdo->query("SELECT id, billet_size FROM m_billet_size ORDER BY billet_size")->fetchAll(PDO::FETCH_ASSOC),
    "bolsters"           => $pdo->query("SELECT id, bolster_name, die_diamater AS diamater_id FROM m_bolster ORDER BY bolster_name")->fetchAll(PDO::FETCH_ASSOC),
]);
