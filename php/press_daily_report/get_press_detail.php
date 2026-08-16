<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "idが不正です"], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        t_press.*,
        m_dies.die_number,
        m_pressing_type.pressing_type,
        m_staff.staff_name,
        m_ordersheet.ordersheet_number,
        DATE_FORMAT(t_press_directive.plan_date_at, '%Y-%m-%d') AS press_directive_plan_date_at,
        DATE_FORMAT(t_press.press_date_at, '%Y-%m-%d') AS press_date_at_fmt,
        DATE_FORMAT(t_press.press_start_at, '%H:%i') AS press_start_at_fmt,
        DATE_FORMAT(t_press.press_finish_at, '%H:%i') AS press_finish_at_fmt
    FROM t_press
    LEFT JOIN m_dies ON t_press.dies_id = m_dies.id
    LEFT JOIN m_pressing_type ON t_press.pressing_type_id = m_pressing_type.id
    LEFT JOIN m_staff ON t_press.staff_id = m_staff.id
    LEFT JOIN m_ordersheet ON t_press.ordersheet_id = m_ordersheet.id
    LEFT JOIN t_press_directive ON t_press.press_directive_id = t_press_directive.id
    WHERE t_press.id = :id
");
$stmt->bindValue(":id", $id, PDO::PARAM_INT);
$stmt->execute();
$press = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$press) {
    http_response_code(404);
    echo json_encode(["error" => "レコードが見つかりません"], JSON_UNESCAPED_UNICODE);
    exit;
}
$press["press_date_at"] = $press["press_date_at_fmt"];
$press["press_start_at"] = $press["press_start_at_fmt"];
$press["press_finish_at"] = $press["press_finish_at_fmt"];

function fetchAllFor($pdo, $sql, $id) {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$bundle = fetchAllFor($pdo, "SELECT bundle, quantity, lot, mfg, insp, note FROM t_bundle WHERE press_id = :id ORDER BY id", $id);
$rack   = fetchAllFor($pdo, "SELECT order_number, rack_number, work_quantity FROM t_using_aging_rack WHERE t_press_id = :id ORDER BY order_number", $id);
$workLength = fetchAllFor($pdo, "SELECT billet_number, work_number, work_length, work_quantity FROM t_press_work_length_quantity WHERE press_id = :id ORDER BY billet_number", $id);
$time = fetchAllFor($pdo, "SELECT Code AS code, DATE_FORMAT(time_start,'%H:%i') AS time_start, DATE_FORMAT(time_end,'%H:%i') AS time_end FROM t_time_press WHERE press_id = :id ORDER BY id", $id);
$pull = fetchAllFor($pdo, "SELECT DATE_FORMAT(pull_date,'%Y-%m-%d') AS date, pull_no1 AS no1, pull_no2 AS no2, DATE_FORMAT(pull_start,'%H:%i') AS start, DATE_FORMAT(pull_end,'%H:%i') AS end FROM t_pull_press WHERE press_id = :id ORDER BY id", $id);
$cut  = fetchAllFor($pdo, "SELECT DATE_FORMAT(cut_date,'%Y-%m-%d') AS date, cut_no1 AS no1, cut_no2 AS no2, DATE_FORMAT(cut_start,'%H:%i') AS start, DATE_FORMAT(cut_end,'%H:%i') AS end FROM t_cut_press WHERE press_id = :id ORDER BY id", $id);

echo json_encode([
    "press" => $press,
    "bundle" => $bundle,
    "rack" => $rack,
    "workLength" => $workLength,
    "time" => $time,
    "pull" => $pull,
    "cut" => $cut,
], JSON_UNESCAPED_UNICODE);
