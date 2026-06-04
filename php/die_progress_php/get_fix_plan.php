<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$press_id = $_GET["press_id"] ?? null;

if (!$press_id) {
    echo json_encode(["error" => "press_id is required"]);
    exit;
}

/* --------------------------------------------------
   1. press_id → inspection（最新1件）
-------------------------------------------------- */
$sql = "
    SELECT *
    FROM t_die_inspection
    WHERE press_id = ?
    ORDER BY id DESC
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$press_id]);
$inspection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inspection) {
    echo json_encode(["error" => "inspection not found"]);
    exit;
}

$inspection_id = $inspection["id"];

/* --------------------------------------------------
   2. inspection_id → diagnosis（最新1件）
-------------------------------------------------- */
$sql = "
    SELECT d.*, s.staff_name AS diagnosis_staff_name
    FROM t_die_diagnosis d
    LEFT JOIN m_staff s ON d.diagnosis_staff = s.id
    WHERE d.inspection_id = ?
    ORDER BY d.id DESC
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$inspection_id]);
$diagnosis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$diagnosis) {
    echo json_encode(["error" => "diagnosis not found"]);
    exit;
}

$diagnosis_id = $diagnosis["id"];

/* --------------------------------------------------
   3. diagnosis_id → 修理計画（t_die_fix）
-------------------------------------------------- */
$sql = "
    SELECT 
        f.*,
        s.staff_name AS plan_fix_staff_name,
        d.ng_action   -- ★ 追加
    FROM t_die_fix f
    LEFT JOIN m_staff s ON f.plan_fix_staff_id = s.id
    LEFT JOIN t_die_diagnosis d ON f.diagnosis_id = d.id   -- ★ 追加
    WHERE f.diagnosis_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$diagnosis_id]);
$fix = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fix) {
    echo json_encode(["error" => "fix plan not found"]);
    exit;
}

$fix_id = $fix["id"];

/* --------------------------------------------------
   4. 添付ファイル（t_die_attachment）
-------------------------------------------------- */
$sql = "
    SELECT id, fix_id, file_path, file_type, created_at
    FROM t_die_attachment
    WHERE fix_id = ?
    ORDER BY id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fix_id]);
$files_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ★ 共通化：フォルダ込みパスに変換 */
$files = [];
foreach ($files_raw as $f) {
    $files[] = [
        "id"        => $f["id"],
        "file_path" => "fix/{$fix_id}/" . $f["file_path"],
        "file_type" => $f["file_type"],
        "created_at" => $f["created_at"]
    ];
}

/* --------------------------------------------------
   5. JSON で返す
-------------------------------------------------- */
/* --------------------------------------------------
   6. 押出日・金型番号（t_press + m_dies）
-------------------------------------------------- */
$sql = "
    SELECT
        m.die_number,
        DATE_FORMAT(p.press_date_at, '%Y-%m-%d') AS press_date
    FROM t_die_inspection i
    LEFT JOIN m_dies m ON i.die_id = m.id
    LEFT JOIN t_press p ON i.press_id = p.id
    WHERE i.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$inspection_id]);
$press_info = $stmt->fetch(PDO::FETCH_ASSOC);

$fix["files"]          = $files;
$fix["inspection_id"]  = $inspection_id;
$fix["fix_id"]         = $fix_id;
$fix["die_number"]     = $press_info["die_number"] ?? null;
$fix["press_date"]     = $press_info["press_date"]  ?? null;

echo json_encode($fix);
