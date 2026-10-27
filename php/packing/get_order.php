<?php
require_once __DIR__ . "/common.php";

// 梱包入力：選んだ発注書の中身
//   GET id = m_ordersheet.id
//   返す：発注書（1箱の本数＝品番の packing_quantity）、その発注の押出、梱包作業（作業者つき）、箱番号、作業者の候補、NG コード
//   exd11 の SelOrderSheetList / SelDieNumberAndPN / SelPackingHistory / SelWorkersList / SelBoxNumberList / SelPackedBoxList /
//   SelStaffList / SelNgCode をまとめたもの

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) pkFail(400, "id が不正です");

try {
    $stmt = $pdo->prepare("
        SELECT
            o.id,
            o.ordersheet_number,
            o.production_numbers_id                     AS production_number_id,
            pn.production_number,
            pn.packing_quantity                         AS per_box,
            DATE_FORMAT(o.delivery_date_at, '%Y-%m-%d') AS delivery_date,
            o.production_quantity                       AS order_qty,
            o.note
        FROM m_ordersheet o
        LEFT JOIN m_production_numbers pn ON pn.id = o.production_numbers_id
        WHERE o.id = :id
    ");
    $stmt->execute([":id" => $id]);
    $order = $stmt->fetch();
    if (!$order) pkFail(404, "発注書が見つかりません");
    foreach (["id", "production_number_id", "order_qty"] as $k) $order[$k] = (int)$order[$k];
    $order["per_box"] = is_numeric($order["per_box"]) && (int)$order["per_box"] > 0 ? (int)$order["per_box"] : null;

    // この発注の押出（新しい順）。梱包完了日は t_press.packing_check_date
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            DATE_FORMAT(p.press_date_at, '%Y-%m-%d')      AS press_date,
            p.press_machine_no                            AS machine,
            d.die_number,
            DATE_FORMAT(p.packing_check_date, '%Y-%m-%d') AS packing_check_date
        FROM t_press p
        LEFT JOIN m_dies d ON d.id = p.dies_id
        WHERE p.ordersheet_id = :id
        ORDER BY p.press_date_at DESC, p.id DESC
    ");
    $stmt->execute([":id" => $id]);
    $presses = array_map(function ($r) {
        $r["id"] = (int)$r["id"];
        $r["machine"] = $r["machine"] === null ? null : (int)$r["machine"];
        return $r;
    }, $stmt->fetchAll());

    // 梱包作業（新しい順）と作業者
    $stmt = $pdo->prepare("
        SELECT
            k.id,
            DATE_FORMAT(k.packing_date, '%Y-%m-%d') AS packing_date,
            TIME_FORMAT(k.packing_start, '%H:%i')   AS start_time,
            TIME_FORMAT(k.packing_end, '%H:%i')     AS end_time,
            (SELECT COUNT(DISTINCT b.box_number_id) FROM t_packing_box b WHERE b.packing_id = k.id) AS box_count,
            (SELECT IFNULL(SUM(b.work_quantity), 0) FROM t_packing_box b WHERE b.packing_id = k.id) AS packed_qty
        FROM t_packing k
        WHERE k.m_ordersheet_id = :id
        ORDER BY k.packing_date DESC, k.packing_start DESC, k.id DESC
    ");
    $stmt->execute([":id" => $id]);
    $works = $stmt->fetchAll();
    $workers = [];
    if ($works) {
        $ids = implode(",", array_map(fn($w) => (int)$w["id"], $works));
        foreach ($pdo->query("
            SELECT w.id, w.t_packing_id, w.m_staff_id, s.staff_name
            FROM t_packing_worker w
            LEFT JOIN m_staff s ON s.id = w.m_staff_id
            WHERE w.t_packing_id IN ({$ids})
            ORDER BY s.position_id DESC, s.emploee_number
        ") as $w) {
            $workers[(int)$w["t_packing_id"]][] = ["id" => (int)$w["id"], "staff_id" => (int)$w["m_staff_id"], "name" => $w["staff_name"]];
        }
    }
    $works = array_map(function ($w) use ($workers) {
        foreach (["id", "box_count", "packed_qty"] as $k) $w[$k] = (int)$w[$k];
        $w["workers"] = $workers[$w["id"]] ?? [];
        return $w;
    }, $works);

    // 箱番号：この発注の箱 と、この発注の押出が入っている箱（別の発注の箱を使ったもの）。入っている本数つき
    $stmt = $pdo->prepare("
        SELECT
            bn.id,
            bn.box_number,
            bn.m_ordersheet_id,
            IFNULL((SELECT SUM(b.work_quantity) FROM t_packing_box b WHERE b.box_number_id = bn.id), 0) AS qty
        FROM t_packing_box_number bn
        WHERE bn.m_ordersheet_id = :id1
           OR bn.id IN (
                SELECT b.box_number_id
                FROM t_packing_box b
                JOIN t_using_aging_rack r ON r.id = b.using_aging_rack_id
                JOIN t_press p ON p.id = r.t_press_id
                WHERE p.ordersheet_id = :id2
           )
        ORDER BY bn.m_ordersheet_id <> :id3, bn.id
    ");
    $stmt->execute([":id1" => $id, ":id2" => $id, ":id3" => $id]);
    $boxes = array_map(fn($b) => [
        "id" => (int)$b["id"], "box_number" => $b["box_number"],
        "ordersheet_id" => (int)$b["m_ordersheet_id"], "qty" => (int)$b["qty"],
    ], $stmt->fetchAll());

    // 作業者の候補：在籍中の全員（ex0.11 と同じ並び）
    $staff = array_map(fn($s) => ["id" => (int)$s["id"], "name" => $s["staff_name"]], $pdo->query("
        SELECT id, staff_name FROM m_staff
        WHERE leave_at IS NULL OR leave_at > CURDATE()
        ORDER BY position_id DESC, emploee_number
    ")->fetchAll());

    // NG コード（3桁のコード）
    $codes = array_map(fn($c) => [
        "id" => (int)$c["id"], "code" => $c["quality_code"], "ja" => $c["description_jp"], "vi" => $c["description_vn"],
    ], $pdo->query("
        SELECT id, quality_code, description_jp, description_vn FROM m_quality_code
        WHERE quality_code REGEXP '^[0-9]{3}$'
        ORDER BY quality_code
    ")->fetchAll());

    echo json_encode([
        "order" => $order, "presses" => $presses, "works" => $works,
        "boxes" => $boxes, "staff" => $staff, "codes" => $codes,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    pkFail(500, $e->getMessage());
}
