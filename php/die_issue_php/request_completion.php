<?php
// ★ DB 接続（あなたの他のファイルと同じ書き方に合わせています）
$host = 'localhost';
$dbname = 'extrusion';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// ★ JSON 受け取り
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'error' => 'No JSON received']);
    exit;
}

$issue_id = $data['issue_id'] ?? null;
$completed_by = $data['completed_by'] ?? null;

if (!$issue_id || !$completed_by) {
    echo json_encode(['status' => 'error', 'error' => 'Missing parameters']);
    exit;
}

// ★ completion_status を completion_requested に更新
$sql = "UPDATE t_die_issue
        SET completion_status = 'completion_requested',
            completed_by = ?,
            completed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?";

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([$completed_by, $issue_id]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}