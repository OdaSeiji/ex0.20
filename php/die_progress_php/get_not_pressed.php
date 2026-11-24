<?php
// まだ一度も押出していない金型（t_press に1行も無い金型）の一覧（die_not_pressed.html 未押出金型一覧）
//   ex0.11 CsvDownLoad.html の「Khuôn chưa đùn」（php/Download/Notpress.php）を画面の一覧として移植（2026-11-24）。
//   型番・品番・到着日だけ。古く届いたものから（到着日が無いものは最後）。
//   到着日は今は m_dies.arrival_at（到着日は t_die_handover に移す予定：documents/2026-11-15_die_tables_reorganization.md）。
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$sql = "
    SELECT
        d.die_number,
        pn.production_number,
        NULLIF(d.arrival_at, '0000-00-00') AS arrival_at
    FROM m_dies d
    LEFT JOIN m_production_numbers pn ON pn.id = d.production_number_id
    WHERE NOT EXISTS (SELECT 1 FROM t_press p WHERE p.dies_id = d.id)
    ORDER BY (d.arrival_at IS NULL OR d.arrival_at = '0000-00-00'), d.arrival_at, d.die_number
";

echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
