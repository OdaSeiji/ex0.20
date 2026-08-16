<?php
function toIntOrNull($v) {
    return ($v === null || $v === "") ? null : (int)$v;
}
function toStrOrNull($v) {
    return ($v === null || $v === "") ? null : (string)$v;
}

function pressColumns() {
    $columns = [
        "dies_id", "is_washed_die", "press_date_at", "pressing_type_id", "press_machine_no",
        "billet_size", "billet_length", "plan_billet_quantities", "actual_billet_quantities",
        "press_start_at", "press_finish_at", "actual_ram_speed", "actual_die_temperature", "staff_id",
        "stretch_ratio", "first_actual_length",
        "container_upside_stemside_temperature", "container_upside_dieside_temperature",
        "container_downside_stemside_temperature", "container_downside_dieide_temperature",
        "press_directive_scan_file_name", "press_directive_id", "ordersheet_id", "special_note",
    ];
    foreach (["no1", "no2", "no3", "no4", "no5"] as $no) {
        foreach (["0200", "1000"] as $pos) {
            foreach (["ram_speed", "ram_pressure", "work_temperature"] as $field) {
                $columns[] = "{$no}_{$pos}_{$field}";
            }
        }
    }
    return $columns;
}

function bindPressValues($stmt, $p) {
    $stmt->bindValue(":dies_id", (int)$p["dies_id"], PDO::PARAM_INT);
    $stmt->bindValue(":is_washed_die", (int)$p["is_washed_die"], PDO::PARAM_INT);
    $stmt->bindValue(":press_date_at", $p["press_date_at"], PDO::PARAM_STR);
    $stmt->bindValue(":pressing_type_id", (int)$p["pressing_type_id"], PDO::PARAM_INT);
    $stmt->bindValue(":press_machine_no", (int)$p["press_machine_no"], PDO::PARAM_INT);
    $stmt->bindValue(":billet_size", (int)$p["billet_size"], PDO::PARAM_INT);
    $stmt->bindValue(":billet_length", (int)$p["billet_length"], PDO::PARAM_INT);
    $stmt->bindValue(":plan_billet_quantities", (int)$p["plan_billet_quantities"], PDO::PARAM_INT);
    $stmt->bindValue(":actual_billet_quantities", (int)$p["actual_billet_quantities"], PDO::PARAM_INT);
    $stmt->bindValue(":press_start_at", $p["press_start_at"], PDO::PARAM_STR);
    $stmt->bindValue(":press_finish_at", $p["press_finish_at"], PDO::PARAM_STR);
    $stmt->bindValue(":actual_ram_speed", $p["actual_ram_speed"], PDO::PARAM_STR);
    $stmt->bindValue(":actual_die_temperature", (int)$p["actual_die_temperature"], PDO::PARAM_INT);
    $stmt->bindValue(":staff_id", (int)$p["staff_id"], PDO::PARAM_INT);
    $stmt->bindValue(":stretch_ratio", toStrOrNull($p["stretch_ratio"] ?? null), PDO::PARAM_STR);
    $stmt->bindValue(":first_actual_length", toStrOrNull($p["first_actual_length"] ?? null), PDO::PARAM_STR);
    $stmt->bindValue(":container_upside_stemside_temperature", (int)$p["container_upside_stemside_temperature"], PDO::PARAM_INT);
    $stmt->bindValue(":container_upside_dieside_temperature", (int)$p["container_upside_dieside_temperature"], PDO::PARAM_INT);
    $stmt->bindValue(":container_downside_stemside_temperature", (int)$p["container_downside_stemside_temperature"], PDO::PARAM_INT);
    $stmt->bindValue(":container_downside_dieide_temperature", (int)$p["container_downside_dieside_temperature"], PDO::PARAM_INT);
    $stmt->bindValue(":press_directive_scan_file_name", toStrOrNull($p["press_directive_scan_file_name"] ?? null), PDO::PARAM_STR);

    $pressDirectiveId = toIntOrNull($p["press_directive_id"] ?? null);
    $stmt->bindValue(":press_directive_id", $pressDirectiveId, $pressDirectiveId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

    $ordersheetId = toIntOrNull($p["ordersheet_id"] ?? null);
    $stmt->bindValue(":ordersheet_id", $ordersheetId, $ordersheetId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

    $stmt->bindValue(":special_note", toStrOrNull($p["special_note"] ?? null), PDO::PARAM_STR);

    foreach (["no1", "no2", "no3", "no4", "no5"] as $no) {
        foreach (["0200", "1000"] as $pos) {
            foreach (["ram_speed", "ram_pressure", "work_temperature"] as $field) {
                $key = "{$no}_{$pos}_{$field}";
                $stmt->bindValue(":{$key}", toStrOrNull($p[$key] ?? null), PDO::PARAM_STR);
            }
        }
    }
}

const PRESS_REQUIRED_KEYS = [
    "dies_id", "is_washed_die", "press_date_at", "pressing_type_id", "press_machine_no",
    "billet_size", "billet_length", "plan_billet_quantities", "actual_billet_quantities",
    "press_start_at", "press_finish_at", "actual_ram_speed", "actual_die_temperature", "staff_id",
];

function saveSubTables($pdo, $pressId, $bundle, $rack, $workLen, $time, $pull, $cut, $pressDateAt) {
    if (count($bundle) > 0) {
        $bStmt = $pdo->prepare("
            INSERT INTO t_bundle (press_id, bundle, quantity, lot, mfg, insp, note)
            VALUES (:press_id, :bundle, :quantity, :lot, :mfg, :insp, :note)
        ");
        foreach ($bundle as $row) {
            $bStmt->execute([
                ":press_id" => $pressId,
                ":bundle"   => $row["bundle"] ?? "",
                ":quantity" => (int)($row["quantity"] ?? 0),
                ":lot"      => $row["lot"] ?? "",
                ":mfg"      => toIntOrNull($row["mfg"] ?? null),
                ":insp"     => toIntOrNull($row["insp"] ?? null),
                ":note"     => toStrOrNull($row["note"] ?? null),
            ]);
        }
    }

    if (count($rack) > 0) {
        $rStmt = $pdo->prepare("
            INSERT INTO t_using_aging_rack (t_press_id, order_number, rack_number, work_quantity)
            VALUES (:press_id, :order_number, :rack_number, :work_quantity)
        ");
        foreach ($rack as $row) {
            $rStmt->execute([
                ":press_id"      => $pressId,
                ":order_number"  => (int)($row["order_number"] ?? 0),
                ":rack_number"   => (int)($row["rack_number"] ?? 0),
                ":work_quantity" => toIntOrNull($row["work_quantity"] ?? null),
            ]);
        }
    }

    if (count($workLen) > 0) {
        $wStmt = $pdo->prepare("
            INSERT INTO t_press_work_length_quantity (press_id, billet_number, work_number, work_length, work_quantity)
            VALUES (:press_id, :billet_number, :work_number, :work_length, :work_quantity)
        ");
        foreach ($workLen as $row) {
            $len = toStrOrNull($row["work_length"] ?? null);
            $qty = toStrOrNull($row["work_quantity"] ?? null);
            if ($len === null && $qty === null) continue;
            $wStmt->execute([
                ":press_id"      => $pressId,
                ":billet_number" => (int)($row["billet_number"] ?? 0),
                ":work_number"   => (int)($row["work_number"] ?? 1),
                ":work_length"   => $len,
                ":work_quantity" => $qty,
            ]);
        }
    }

    if (count($time) > 0) {
        $tStmt = $pdo->prepare("
            INSERT INTO t_time_press (press_id, Code, time_start, time_end, time_note, time_date)
            VALUES (:press_id, :code, :time_start, :time_end, :time_note, :time_date)
        ");
        foreach ($time as $row) {
            $tStmt->execute([
                ":press_id"   => $pressId,
                ":code"       => (int)($row["code"] ?? 0),
                ":time_start" => $row["time_start"] ?? "",
                ":time_end"   => $row["time_end"] ?? "",
                ":time_note"  => toStrOrNull($row["time_note"] ?? null),
                ":time_date"  => $pressDateAt,
            ]);
        }
    }

    if (count($pull) > 0) {
        $plStmt = $pdo->prepare("
            INSERT INTO t_pull_press (press_id, pull_date, pull_no1, pull_no2, pull_start, pull_end)
            VALUES (:press_id, :pull_date, :pull_no1, :pull_no2, :pull_start, :pull_end)
        ");
        foreach ($pull as $row) {
            $plStmt->execute([
                ":press_id"   => $pressId,
                ":pull_date"  => $row["date"] ?? "",
                ":pull_no1"   => (int)($row["no1"] ?? 0),
                ":pull_no2"   => (int)($row["no2"] ?? 0),
                ":pull_start" => $row["start"] ?? "",
                ":pull_end"   => $row["end"] ?? "",
            ]);
        }
    }

    if (count($cut) > 0) {
        $ctStmt = $pdo->prepare("
            INSERT INTO t_cut_press (press_id, cut_date, cut_no1, cut_no2, cut_start, cut_end)
            VALUES (:press_id, :cut_date, :cut_no1, :cut_no2, :cut_start, :cut_end)
        ");
        foreach ($cut as $row) {
            $ctStmt->execute([
                ":press_id"  => $pressId,
                ":cut_date"  => $row["date"] ?? "",
                ":cut_no1"   => (int)($row["no1"] ?? 0),
                ":cut_no2"   => (int)($row["no2"] ?? 0),
                ":cut_start" => $row["start"] ?? "",
                ":cut_end"   => $row["end"] ?? "",
            ]);
        }
    }
}
