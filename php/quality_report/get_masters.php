<?php
require_once __DIR__ . "/common.php";

// 入力に使うマスタ：NG コード（全コード＋「0 不良なし」）、在籍中の検査員
//   コード 0 は「チェックしたが不良なし」の記録（本数0）。NG が無い押出でも判定者が残る（寸法で多用されている）
try {
    $codes = $pdo->query("
        SELECT id, quality_code, description_jp, description_vn
        FROM m_quality_code
        WHERE quality_code REGEXP '^[0-9]{3}$' OR quality_code = '0'
        ORDER BY quality_code = '0' DESC, quality_code
    ")->fetchAll();
    foreach ($codes as &$c) $c["id"] = (int)$c["id"];
    unset($c);

    $inspectors = $pdo->query("
        SELECT id, staff_name
        FROM m_staff
        WHERE role = 'inspector' AND leave_at IS NULL
        ORDER BY staff_name
    ")->fetchAll();
    foreach ($inspectors as &$s) $s["id"] = (int)$s["id"];
    unset($s);

    echo json_encode(["codes" => $codes, "inspectors" => $inspectors], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    qrFail(500, $e->getMessage());
}
