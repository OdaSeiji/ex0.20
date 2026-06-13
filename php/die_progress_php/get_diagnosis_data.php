<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$press_id = $_GET["press_id"];

// --------------------------------------------------
// 1. 押出実績（t_press）
// --------------------------------------------------
$sql = "
SELECT 
    p.*,
    d.die_number
FROM t_press p
LEFT JOIN m_dies d ON p.dies_id = d.id
WHERE p.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$press_id]);
$press = $stmt->fetch(PDO::FETCH_ASSOC);

$die_id = $press["dies_id"];

// --------------------------------------------------
// 1-b. 金型コンディション（m_dies → m_die_conditions）
// --------------------------------------------------
$die_condition_id   = null;
$die_condition_name = null;
if ($die_id) {
    $sql = "
    SELECT md.die_condition_id, mc.name AS die_condition_name
    FROM m_dies md
    LEFT JOIN m_die_conditions mc ON md.die_condition_id = mc.id
    WHERE md.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$die_id]);
    $cond = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cond) {
        $die_condition_id   = $cond["die_condition_id"];
        $die_condition_name = $cond["die_condition_name"];
    }
}

// --------------------------------------------------
// 2. 押出指示（t_press_directive）
// --------------------------------------------------
$sql = "
SELECT *
FROM t_press_directive
WHERE dies_id = ?
ORDER BY id DESC
LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$die_id]);
$directive = $stmt->fetch(PDO::FETCH_ASSOC);

// --------------------------------------------------
// 3. 測定情報（t_die_inspection）
// --------------------------------------------------
$sql = "
SELECT 
    i.*,
    s.staff_name
FROM t_die_inspection i
LEFT JOIN m_staff s ON i.inspection_staff_id = s.id
WHERE i.press_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$press_id]);
$inspection = $stmt->fetch(PDO::FETCH_ASSOC);

$inspection_id = $inspection ? $inspection["id"] : null;

// --------------------------------------------------
// 4. 測定添付ファイル（t_die_attachment）
// --------------------------------------------------
$inspection_files = [];
if ($inspection_id) {
    $sql = "
    SELECT 
        id,
        file_path,
        file_type,
        created_at
    FROM t_die_attachment
    WHERE inspection_id = ?
    ORDER BY id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inspection_id]);
    $inspection_files_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($inspection_files_raw as $f) {
        $inspection_files[] = [
            "id"        => $f["id"],
            "file_path" => "inspection/{$inspection_id}/" . $f["file_path"],
            "file_type" => $f["file_type"],
            "created_at" => $f["created_at"]
        ];
    }
}

// --------------------------------------------------
// 5. 診断情報（t_die_diagnosis）
// --------------------------------------------------
$diagnosis = null;
if ($inspection_id) {
    $sql = "
    SELECT 
        d.*,
        s.staff_name AS diagnosis_staff_name
    FROM t_die_diagnosis d
    LEFT JOIN m_staff s ON d.diagnosis_staff = s.id
    WHERE d.inspection_id = ?
    ORDER BY d.id DESC
    LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inspection_id]);
    $diagnosis = $stmt->fetch(PDO::FETCH_ASSOC);
}

$diagnosis_id = $diagnosis ? $diagnosis["id"] : null;

// --------------------------------------------------
// 6. 診断添付ファイル（t_die_attachment）
// --------------------------------------------------
$diagnosis_files = [];
if ($diagnosis_id) {
    $sql = "
        SELECT 
            id,
            file_path,
            file_type,
            created_at
        FROM t_die_attachment
        WHERE diagnosis_id = ?
        ORDER BY id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$diagnosis_id]);
    $diagnosis_files_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($diagnosis_files_raw as $f) {
        $diagnosis_files[] = [
            "id"        => $f["id"],
            "file_path" => "diagnosis/{$diagnosis_id}/" . $f["file_path"],
            "file_type" => $f["file_type"],
            "created_at" => $f["created_at"]
        ];
    }
}


// --------------------------------------------------
// 7. 修理計画（未使用のため null）
// --------------------------------------------------
$fix_plan = null;
$fix_plan_id = null;

// --------------------------------------------------
// 8. 修理実行（t_die_fix）
// --------------------------------------------------
$fix = null;
if ($fix_plan_id) {
    $sql = "
    SELECT *
    FROM t_die_fix
    WHERE fix_plan_id = ?
    ORDER BY id DESC
    LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fix_plan_id]);
    $fix = $stmt->fetch(PDO::FETCH_ASSOC);
}

$fix_id = $fix ? $fix["id"] : null;

// --------------------------------------------------
// 9. 修理添付ファイル（t_die_fix_attachment）
// --------------------------------------------------
$fix_files = [];
if ($fix_id) {
    $sql = "
    SELECT 
        id, file_name, original_name, file_type, uploaded_at
    FROM t_die_fix_attachment
    WHERE fix_id = ?
    ORDER BY id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fix_id]);
    $fix_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --------------------------------------------------
// 10. 修理承認（t_die_fix_approval）
// --------------------------------------------------
$fix_approval = null;
if ($fix_id) {
    $sql = "
    SELECT *
    FROM t_die_fix_approval
    WHERE fix_id = ?
    ORDER BY id DESC
    LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fix_id]);
    $fix_approval = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --------------------------------------------------
// まとめて返す
// --------------------------------------------------
echo json_encode([
    "press" => $press,
    "die_condition_id"   => $die_condition_id,
    "die_condition_name" => $die_condition_name,
    "directive" => $directive,
    "inspection" => $inspection,
    "inspection_files" => $inspection_files,
    "diagnosis" => $diagnosis,
    "diagnosis_files" => $diagnosis_files,
    "fix_plan" => $fix_plan,
    "fix" => $fix,
    "fix_files" => $fix_files,
    "fix_approval" => $fix_approval,
    "press_directive_scan_file_name" => $press["press_directive_scan_file_name"]
], JSON_UNESCAPED_UNICODE);
