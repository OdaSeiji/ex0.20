<?php

function saveVariantOptions($pdo, $pnId, $defaultCode, $defaultLength, array $extraVariants) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO m_production_number_variants (production_number_id, length, production_number, is_default)
        VALUES (:pn_id, :length, :code, :is_default)
    ");

    $stmt->execute([
        ":pn_id"      => $pnId,
        ":length"     => $defaultLength,
        ":code"       => $defaultCode,
        ":is_default" => 1,
    ]);

    foreach ($extraVariants as $variant) {
        $stmt->execute([
            ":pn_id"      => $pnId,
            ":length"     => $variant["length"],
            ":code"       => $variant["production_number"],
            ":is_default" => 0,
        ]);
    }
}

function parseVariantOptions($raw) {
    if (!$raw) return [];
    $rows = json_decode($raw, true);
    if (!is_array($rows)) return [];

    $result = [];
    $seenCodes = [];
    foreach ($rows as $row) {
        $code   = trim($row["production_number"] ?? "");
        $length = trim((string)($row["length"] ?? ""));
        if ($code === "" || $length === "" || !is_numeric($length)) continue;
        if (isset($seenCodes[$code])) continue;
        $seenCodes[$code] = true;
        $result[] = ["production_number" => $code, "length" => $length];
    }
    return $result;
}
