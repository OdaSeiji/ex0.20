<?php
function toIntOrNull($v) {
    return ($v === null || $v === "") ? null : (int)$v;
}
function toStrOrNull($v) {
    return ($v === null || $v === "") ? null : (string)$v;
}

function directiveColumns() {
    return [
        "dies_id", "ordersheet_id", "production_number_variant_id", "plan_date_at",
        "pressing_type_id", "discard_thickness", "ram_speed", "billet_size", "billet_length",
        "billet_input_quantity", "billet_temperature", "billet_taper_heating",
        "die_temperature", "die_heating_time", "stretch_ratio", "incharge_person_id",
        "value_l", "value_m", "value_n", "nbn_id", "press_machine", "cooling_type",
        "sub_initial", "initial", "previous_press_note",
    ];
}

function bindDirectiveValues($stmt, $p) {
    $stmt->bindValue(":dies_id", (int)$p["dies_id"], PDO::PARAM_INT);

    foreach (["ordersheet_id", "production_number_variant_id", "pressing_type_id", "discard_thickness",
              "billet_size", "billet_length", "billet_input_quantity", "billet_temperature",
              "billet_taper_heating", "die_temperature", "incharge_person_id", "value_m", "value_n",
              "nbn_id", "press_machine", "cooling_type", "sub_initial", "initial"] as $col) {
        $v = toIntOrNull($p[$col] ?? null);
        $stmt->bindValue(":{$col}", $v, $v === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    }

    foreach (["plan_date_at", "ram_speed", "die_heating_time", "stretch_ratio", "value_l",
              "previous_press_note"] as $col) {
        $stmt->bindValue(":{$col}", toStrOrNull($p[$col] ?? null), PDO::PARAM_STR);
    }
}
