<?php
require_once __DIR__ . "/common.php";

// 選んだ押出の詳細：基本情報・工程の完了日・QC、ラック（本数・NG・OK）、NG の記録、NG コード別の合計
$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) qrFail(400, "id が不正です");

try {
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            DATE_FORMAT(p.press_date_at, '%Y-%m-%d') AS press_date,
            TIME_FORMAT(p.press_start_at, '%H:%i')   AS press_start,
            p.press_machine_no                       AS machine,
            p.pressing_type_id,
            t.pressing_type,
            p.actual_billet_quantities               AS billets,
            d.die_number,
            COALESCE(pnv.production_number, pnd.production_number) AS production_number,
            DATE_FORMAT(p.dimension_check_date, '%Y-%m-%d') AS dimension_date,
            DATE_FORMAT(p.etching_check_date, '%Y-%m-%d')   AS etching_date,
            DATE_FORMAT(p.hardness_check_date, '%Y-%m-%d')  AS aging_date,
            DATE_FORMAT(p.qc_check_date, '%Y-%m-%d')        AS qc_date,
            p.qc_staff_id,
            qs.staff_name                            AS qc_staff_name,
            " . QR_DONE_SQL . "                      AS qc_done,
            " . QR_WINDOW_SQL . "                    AS in_window
        FROM t_press p
        LEFT JOIN m_pressing_type t ON t.id = p.pressing_type_id
        LEFT JOIN m_dies d ON d.id = p.dies_id
        LEFT JOIN m_die_production_number_variants v ON v.id = p.die_production_number_variant_id
        LEFT JOIN m_production_numbers pnv ON pnv.id = v.production_number_id
        LEFT JOIN m_production_numbers pnd ON pnd.id = d.production_number_id
        LEFT JOIN m_staff qs ON qs.id = p.qc_staff_id
        WHERE p.id = :id
    ");
    $stmt->execute([":id" => $id]);
    $press = $stmt->fetch();
    if (!$press) qrFail(404, "押出が見つかりません");
    foreach (["id", "machine", "pressing_type_id", "billets", "qc_staff_id"] as $k) {
        $press[$k] = $press[$k] === null ? null : (int)$press[$k];
    }
    $press["qc_done"]   = (bool)$press["qc_done"];
    $press["in_window"] = (bool)$press["in_window"];
    $press["qc_legacy"] = $press["qc_done"] && $press["qc_date"] === null;
    $press["missing_steps"] = qrMissingSteps([
        "pressing_type_id"     => $press["pressing_type_id"],
        "dimension_check_date" => $press["dimension_date"],
        "etching_check_date"   => $press["etching_date"],
        "hardness_check_date"  => $press["aging_date"],
    ]);

    $stmt = $pdo->prepare("
        SELECT r.id, r.order_number, r.rack_number, IFNULL(r.work_quantity, 0) AS qty,
               IFNULL(SUM(q.ng_quantities), 0) AS ng
        FROM t_using_aging_rack r
        LEFT JOIN t_press_quality q ON q.using_aging_rack_id = r.id AND q.process_id IN (1, 2, 3)
        WHERE r.t_press_id = :id
        GROUP BY r.id
        ORDER BY r.order_number, r.id
    ");
    $stmt->execute([":id" => $id]);
    $racks = array_map(function ($r) {
        foreach (["id", "order_number", "rack_number", "qty", "ng"] as $k) $r[$k] = (int)$r[$k];
        $r["ok"] = $r["qty"] - $r["ng"];
        return $r;
    }, $stmt->fetchAll());

    $stmt = $pdo->prepare("
        SELECT q.id, q.using_aging_rack_id AS rack_id, q.process_id, q.quality_code_id,
               c.quality_code, q.ng_quantities AS qty, q.staff_id, s.staff_name, q.note,
               DATE_FORMAT(q.created_at, '%Y-%m-%d') AS created_at
        FROM t_press_quality q
        JOIN t_using_aging_rack r ON r.id = q.using_aging_rack_id
        LEFT JOIN m_quality_code c ON c.id = q.quality_code_id
        LEFT JOIN m_staff s ON s.id = q.staff_id
        WHERE r.t_press_id = :id AND q.process_id IN (1, 2, 3)
        ORDER BY r.order_number, q.process_id, c.quality_code, q.id
    ");
    $stmt->execute([":id" => $id]);
    $ngs = array_map(function ($r) {
        foreach (["id", "rack_id", "process_id", "quality_code_id", "qty", "staff_id"] as $k) {
            $r[$k] = $r[$k] === null ? null : (int)$r[$k];
        }
        return $r;
    }, $stmt->fetchAll());

    echo json_encode(["press" => $press, "racks" => $racks, "ngs" => $ngs], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    qrFail(500, $e->getMessage());
}
