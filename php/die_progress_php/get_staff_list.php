<?php
require_once '../db.php';

$role  = $_GET['role']  ?? null;   // 単一role: ?role=inspector
$roles = $_GET['roles'] ?? null;   // 複数role: ?roles=die_setup,admin,null

if ($roles !== null) {
    $list = array_map('trim', explode(',', $roles));
    $includeNull = in_array('null', $list);
    $list = array_filter($list, fn($r) => $r !== 'null');

    $conditions = [];
    $params = [];
    if (count($list)) {
        $placeholders = implode(',', array_fill(0, count($list), '?'));
        $conditions[] = "role IN ($placeholders)";
        $params = array_merge($params, array_values($list));
    }
    if ($includeNull) {
        $conditions[] = "role IS NULL";
    }
    $where = implode(' OR ', $conditions);

    $sql = "
        SELECT id, staff_name
        FROM m_staff
        WHERE leave_at IS NULL
          AND ($where)
        ORDER BY staff_name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} elseif ($role) {
    $sql = "
        SELECT id, staff_name
        FROM m_staff
        WHERE leave_at IS NULL
          AND role = ?
        ORDER BY staff_name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$role]);
} else {
    $sql = "
        SELECT id, staff_name
        FROM m_staff
        WHERE leave_at IS NULL
        ORDER BY staff_name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows);
