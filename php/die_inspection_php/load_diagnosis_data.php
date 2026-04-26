<?php
require_once "./../db.php";

$inspection_id = $_GET["inspection_id"];

/* ---------------------------------------------------------
   ① Inspection（検査情報）
--------------------------------------------------------- */
$sql = "SELECT i.*, d.die_number
        FROM t_die_inspection i
        LEFT JOIN m_dies d ON i.die_id = d.id
        WHERE i.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$inspection_id]);
$inspection = $stmt->fetch(PDO::FETCH_ASSOC);

$die_id  = $inspection["die_id"];
$press_id = $inspection["press_id"];

/* ---------------------------------------------------------
   ② Inspection Images（検査画像）
   絶対パス: /ex0.20/uploads/inspection/{inspection_id}/{file_name}
--------------------------------------------------------- */
$sql = "SELECT file_name 
        FROM t_die_attachment
        WHERE inspection_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$inspection_id]);

$inspection_images = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $inspection_images[] = "/ex0.20/uploads/inspection/" . $inspection_id . "/" . $row["file_name"];
}

/* ---------------------------------------------------------
   ③ Press（押出実績）
   t_die_inspection.press_id → t_press.id
--------------------------------------------------------- */
$press = null;

if ($press_id) {
    $sql = "SELECT *
            FROM t_press
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$press_id]);
    $press = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ---------------------------------------------------------
   ④ Directive（押出指示）
   t_press.press_directive_id → t_press_directive.id
--------------------------------------------------------- */
$directive = null;

if ($press && isset($press["press_directive_id"])) {
    $sql = "SELECT *
            FROM t_press_directive
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$press["press_directive_id"]]);
    $directive = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ---------------------------------------------------------
   ⑤ Diagnosis History（診断履歴 + 診断者名 + 診断画像）
--------------------------------------------------------- */
$sql = "SELECT d.*, s.staff_name
        FROM t_die_diagnosis d
        LEFT JOIN m_staff s ON d.diagnosed_by = s.id
        WHERE d.inspection_id = ?
        ORDER BY d.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$inspection_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 診断画像を history に追加 */
foreach ($history as &$h) {
    $sql = "SELECT file_name
            FROM t_die_attachment
            WHERE diagnosis_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$h["id"]]);

    $h["images"] = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $h["images"][] = "/ex0.20/uploads/diagnosis/" . $h["id"] . "/" . $row["file_name"];
    }
}

/* ---------------------------------------------------------
   ⑥ JSON で返す
--------------------------------------------------------- */
echo json_encode([
    "inspection"         => $inspection,
    "inspection_images"  => $inspection_images,
    "press"              => $press,
    "directive"          => $directive,
    "history"            => $history
]);
