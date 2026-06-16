<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "./../db.php";

// インポート一覧（1行1件、JOINなし）
$sqlImport = "
    SELECT
        t.id,
        t.die_number,
        t.die_id,
        t.note2,
        t.import_error,
        EXISTS (
            SELECT 1 FROM t_die_handover h WHERE h.die_id = t.die_id
        ) AS dup_warning
    FROM t_parts_import_tmp t
    ORDER BY t.id ASC
";
$imports = $pdo->query($sqlImport)->fetchAll(PDO::FETCH_ASSOC);

// 対象 die_id の既存 t_die_handover レコード
$existing = [];
if (!empty($imports)) {
    $dieIds      = array_column($imports, "die_id");
    $placeholders = implode(",", array_fill(0, count($dieIds), "?"));
    $sqlExisting = "
        SELECT
            h.id,
            h.die_id,
            d.die_number,
            h.is_accessory_item_flag,
            h.note2,
            h.created_at
        FROM t_die_handover h
        JOIN m_dies d ON d.id = h.die_id
        WHERE h.die_id IN ({$placeholders})
        ORDER BY h.die_id ASC, h.id ASC
    ";
    $stmt = $pdo->prepare($sqlExisting);
    $stmt->execute($dieIds);
    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    "imports"  => $imports,
    "existing" => $existing,
]);
