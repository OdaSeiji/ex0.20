<?php
require_once __DIR__ . "/common.php";

// 一覧：期間内（今日から1か月前まで）の押出を、プレス種別・未完了/完了済みで絞って返す
//   GET type = m_pressing_type.id（1=〇, 2=◎, 3=●）, status = open | done
//   counts はタブ用（種別ごとの未完了・完了済みの件数）

$type   = (int)($_GET["type"] ?? 3);
$status = ($_GET["status"] ?? "open") === "done" ? "done" : "open";

try {
    $counts = [];
    $stmt = $pdo->query("
        SELECT p.pressing_type_id AS type,
               SUM(NOT " . QR_DONE_SQL . ") AS open_count,
               SUM(" . QR_DONE_SQL . ")     AS done_count
        FROM t_press p
        WHERE " . QR_WINDOW_SQL . "
        GROUP BY p.pressing_type_id
    ");
    foreach ($stmt as $r) {
        $counts[(int)$r["type"]] = ["open" => (int)$r["open_count"], "done" => (int)$r["done_count"]];
    }

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            DATE_FORMAT(p.press_date_at, '%Y-%m-%d')   AS press_date,
            TIME_FORMAT(p.press_start_at, '%H:%i')     AS press_start,
            p.press_machine_no                         AS machine,
            d.die_number,
            COALESCE(pnv.production_number, pnd.production_number) AS production_number,
            DATE_FORMAT(p.dimension_check_date, '%Y-%m-%d') AS dimension_date,
            DATE_FORMAT(p.etching_check_date, '%Y-%m-%d')   AS etching_date,
            DATE_FORMAT(p.hardness_check_date, '%Y-%m-%d')  AS aging_date,
            DATE_FORMAT(p.qc_check_date, '%Y-%m-%d')        AS qc_date,
            " . QR_DONE_SQL . "                        AS qc_done,
            qs.staff_name                              AS qc_staff_name,
            DATEDIFF(CURDATE(), p.press_date_at)       AS days,
            DATEDIFF(DATE_ADD(p.press_date_at, INTERVAL 1 MONTH), CURDATE()) AS days_left,
            IFNULL(rk.racks, 0)                        AS racks,
            IFNULL(rk.qty, 0)                          AS qty,
            IFNULL(ng.ng, 0)                           AS ng
        FROM t_press p
        LEFT JOIN m_dies d ON d.id = p.dies_id
        LEFT JOIN m_die_production_number_variants v ON v.id = p.die_production_number_variant_id
        LEFT JOIN m_production_numbers pnv ON pnv.id = v.production_number_id
        LEFT JOIN m_production_numbers pnd ON pnd.id = d.production_number_id
        LEFT JOIN m_staff qs ON qs.id = p.qc_staff_id
        LEFT JOIN (
            SELECT t_press_id, COUNT(*) AS racks, SUM(work_quantity) AS qty
            FROM t_using_aging_rack GROUP BY t_press_id
        ) rk ON rk.t_press_id = p.id
        LEFT JOIN (
            SELECT r.t_press_id, SUM(q.ng_quantities) AS ng
            FROM t_press_quality q
            JOIN t_using_aging_rack r ON r.id = q.using_aging_rack_id
            WHERE q.process_id IN (1, 2, 3)
            GROUP BY r.t_press_id
        ) ng ON ng.t_press_id = p.id
        WHERE " . QR_WINDOW_SQL . "
          AND p.pressing_type_id = :type
          AND " . ($status === "done" ? QR_DONE_SQL : "NOT " . QR_DONE_SQL) . "
        ORDER BY p.press_date_at " . ($status === "done" ? "DESC" : "ASC") . ", p.press_start_at, p.id
    ");
    $stmt->execute([":type" => $type]);
    $rows = array_map(function ($r) {
        foreach (["id", "machine", "days", "days_left", "racks", "qty", "ng"] as $k) $r[$k] = (int)$r[$k];
        $r["qc_done"] = (bool)$r["qc_done"];
        // 旧画面（V7）で確認済み：qc_check_id だけ入っていて、確認日・確認者が無い
        $r["qc_legacy"] = $r["qc_done"] && $r["qc_date"] === null;
        return $r;
    }, $stmt->fetchAll());

    echo json_encode(["counts" => (object)$counts, "rows" => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    qrFail(500, $e->getMessage());
}
