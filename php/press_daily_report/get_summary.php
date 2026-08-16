<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$machine = isset($_GET["machine"]) && $_GET["machine"] !== "" ? (int)$_GET["machine"] : null;
$type    = isset($_GET["type"])    && $_GET["type"]    !== "" ? (int)$_GET["type"]    : null;
$die     = isset($_GET["die"])     && $_GET["die"]     !== "" ? trim($_GET["die"])     : null;
$start   = isset($_GET["start"])   && $_GET["start"]   !== "" ? $_GET["start"]         : null;
$end     = isset($_GET["end"])     && $_GET["end"]     !== "" ? $_GET["end"]           : null;

$where  = [];
$params = [];

if ($machine !== null) {
    $where[] = "t_press.press_machine_no = :machine";
    $params[":machine"] = $machine;
}
if ($type !== null) {
    $where[] = "t_press.pressing_type_id = :type";
    $params[":type"] = $type;
}
if ($die !== null) {
    $where[] = "m_dies.die_number LIKE :die";
    $params[":die"] = "%{$die}%";
}
if ($start !== null) {
    $where[] = "t_press.press_date_at >= :start";
    $params[":start"] = $start;
}
if ($end !== null) {
    $where[] = "t_press.press_date_at <= :end";
    $params[":end"] = $end;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$prepare = $pdo->prepare("
    SELECT
        t_press.id,
        DATE_FORMAT(t_press.press_date_at, '%Y-%m-%d') AS press_date_at,
        t_press.press_machine_no,
        m_dies.die_number,
        m_ordersheet.ordersheet_number,
        m_pressing_type.pressing_type,
        t_press.is_washed_die,
        t_press.billet_lot_number,
        t_press.billet_size,
        t_press.billet_length,
        t_press.plan_billet_quantities,
        t_press.actual_billet_quantities,
        m_press_stop_code.stop_code,
        m_press_stop_code.remarks_jp AS stop_cause_remarks,
        t_press.press_start_at,
        t_press.press_finish_at,
        t_press.actual_ram_speed,
        t_press.actual_die_temperature,
        t_press.press_directive_scan_file_name
    FROM t_press
    LEFT JOIN m_dies ON t_press.dies_id = m_dies.id
    LEFT JOIN m_ordersheet ON t_press.ordersheet_id = m_ordersheet.id
    LEFT JOIN m_pressing_type ON t_press.pressing_type_id = m_pressing_type.id
    LEFT JOIN m_press_stop_code ON t_press.press_stop_cause_id = m_press_stop_code.id
    {$whereSql}
    ORDER BY t_press.press_date_at DESC, t_press.id DESC
    LIMIT 300
");
foreach ($params as $key => $value) {
    $prepare->bindValue($key, $value);
}
$prepare->execute();

echo json_encode($prepare->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
