<?php

function saveLengthOptions($pdo, $pnId, $defaultLength, array $extraLengths) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO m_production_number_lengths (production_number_id, length, is_default)
        VALUES (:pn_id, :length, :is_default)
    ");

    $stmt->execute([
        ":pn_id"      => $pnId,
        ":length"     => $defaultLength,
        ":is_default" => 1,
    ]);

    foreach ($extraLengths as $length) {
        $stmt->execute([
            ":pn_id"      => $pnId,
            ":length"     => $length,
            ":is_default" => 0,
        ]);
    }
}

function parseLengthOptions($raw) {
    if (!$raw) return [];
    $values = array_map("trim", explode(",", $raw));
    $values = array_filter($values, fn($v) => $v !== "" && is_numeric($v));
    return array_values(array_unique($values));
}
