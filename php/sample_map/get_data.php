<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

// 【テスト】押出材・製品・サンプル位置の図（sample_map_test.html）用
//   GET date=YYYY-MM-DD → その日の押出の一覧
//   GET id=t_press.id   → 押出の情報（nBn、ビレット数）と、押出材ごとの長さ・製品本数（t_press_work_length_quantity）

$isDate = fn($s) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);

try {
    if (isset($_GET["id"])) {
        $id = (int)$_GET["id"];
        $stmt = $pdo->prepare("
            SELECT p.id, DATE_FORMAT(p.press_date_at, '%Y-%m-%d') AS press_date,
                   TIME_FORMAT(p.press_start_at, '%H:%i') AS press_start,
                   p.press_machine_no AS machine, d.die_number,
                   COALESCE(pnv.production_number, pnd.production_number) AS production_number,
                   COALESCE(pnv.production_length, pnd.production_length) AS product_length,
                   p.actual_billet_quantities AS billets, p.billet_length,
                   n.nbn
            FROM t_press p
            LEFT JOIN m_dies d ON d.id = p.dies_id
            LEFT JOIN m_die_production_number_variants v ON v.id = p.die_production_number_variant_id
            LEFT JOIN m_production_numbers pnv ON pnv.id = v.production_number_id
            LEFT JOIN m_production_numbers pnd ON pnd.id = d.production_number_id
            LEFT JOIN t_press_directive pd ON pd.id = p.press_directive_id
            LEFT JOIN m_nbn n ON n.id = pd.nbn_id
            WHERE p.id = :id
        ");
        $stmt->execute([":id" => $id]);
        $press = $stmt->fetch();
        if (!$press) {
            http_response_code(404);
            echo json_encode(["error" => "押出が見つかりません"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT billet_number AS no, work_length AS length, IFNULL(work_quantity, 0) AS qty
            FROM t_press_work_length_quantity
            WHERE press_id = :id
            ORDER BY billet_number, id
        ");
        $stmt->execute([":id" => $id]);
        $strands = array_map(fn($r) => [
            "no"     => (int)$r["no"],
            "length" => $r["length"] === null ? null : (float)$r["length"],
            "qty"    => (int)$r["qty"],
        ], $stmt->fetchAll());

        // 参考：エッチングで実際に記録されている位置の名前
        $stmt = $pdo->prepare("SELECT Position FROM t_etching WHERE press_id = :id ORDER BY id");
        $stmt->execute([":id" => $id]);
        $etching = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 参考：表面粗さ・ダイスマーク（t_measurement）で実際に記録されている位置の名前
        $stmt = $pdo->prepare("SELECT position FROM t_measurement WHERE press_id = :id ORDER BY id");
        $stmt->execute([":id" => $id]);
        $measurement = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            "press" => $press, "strands" => $strands,
            "etching_positions" => $etching, "measurement_positions" => $measurement,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $date = $_GET["date"] ?? "";
    if (!$isDate($date)) $date = date("Y-m-d");
    $stmt = $pdo->prepare("
        SELECT p.id, TIME_FORMAT(p.press_start_at, '%H:%i') AS press_start, p.press_machine_no AS machine,
               d.die_number, n.nbn, p.actual_billet_quantities AS billets,
               (SELECT COUNT(*) FROM t_press_work_length_quantity w WHERE w.press_id = p.id) AS strands
        FROM t_press p
        LEFT JOIN m_dies d ON d.id = p.dies_id
        LEFT JOIN t_press_directive pd ON pd.id = p.press_directive_id
        LEFT JOIN m_nbn n ON n.id = pd.nbn_id
        WHERE p.press_date_at = :date
        ORDER BY p.press_machine_no, p.press_start_at, p.id
    ");
    $stmt->execute([":date" => $date]);
    echo json_encode(["date" => $date, "presses" => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
