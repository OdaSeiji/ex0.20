<?php
header("Content-Type: application/json; charset=UTF-8");

// エラー表示（調査用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ★ db.php を読み込む（$pdo が使えるようになる）
require_once "../db.php";

try {
    // ★ $pdo は db.php 内で作られているので、そのまま使える
    $sql = "
        SELECT 
            i.id AS issue_id,
            i.die_id,
            d.die_number,
            i.issue_title,
            i.priority,
            i.completion_status,
            (
                SELECT dd.diagnosis_date
                FROM t_die_diagnosis dd
                WHERE dd.issue_id = i.id
                ORDER BY dd.diagnosis_date DESC
                LIMIT 1
            ) AS latest_diagnosis_date
        FROM t_die_issue i
        JOIN m_dies d ON d.id = i.die_id
        ORDER BY i.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Priority ラベル
    foreach ($rows as &$row) {
        switch ($row["priority"]) {
            case 1: $row["priority_label"] = "High"; break;
            case 2: $row["priority_label"] = "Medium"; break;
            case 3: $row["priority_label"] = "Low"; break;
            default: $row["priority_label"] = "Unknown";
        }
    }

    echo json_encode($rows);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
