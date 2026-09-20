<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../db.php";

$targetId = (int)($_GET["id"] ?? 0);
if ($targetId === 0) {
    echo json_encode([]);
    exit;
}

// 品番の解決: 新経路(die_production_number_variant_id)を優先し、
// 未設定の場合は旧経路(m_dies.production_number_id、既定品番)にフォールバックする
$stmt = $pdo->prepare("
    SELECT
        t_press_directive.id,
        m_dies.die_number,
        pn.production_number,
        t_press_directive.plan_date_at,
        m_pressing_type.pressing_type,
        pn.production_length,
        CASE pn.billet_material_id
            WHEN 1 THEN '6061'
            WHEN 2 THEN '6063'
            WHEN 3 THEN '6N01A'
            ELSE '6N01'
        END AS material,
        ROUND(pn.specific_weight, 2) AS specific_weight,
        CASE t_press_directive.billet_size
            WHEN 9 THEN (237 * 237 * 3.1415 / 4) / (m_dies.hole * pn.cross_section_area)
            WHEN 12 THEN (312 * 312 * 3.1415 / 4) / (m_dies.hole * pn.cross_section_area)
            WHEN 14 THEN (366 * 366 * 3.1415 / 4) / (m_dies.hole * pn.cross_section_area)
            ELSE 0
        END AS ratio,
        m_nbn.nbn,
        t_press_directive.previous_press_note,
        m_staff.staff_name,
        t_press_directive.created_at AS issue_date,
        '999' AS plan_pressing_time,
        t_press_directive.billet_input_quantity,
        t_press_directive.sub_initial,
        t_press_directive.billet_length,
        t_press_directive.discard_thickness,
        t_press_directive.ram_speed,
        ROUND(((CASE t_press_directive.billet_size
                    WHEN 9 THEN 132.3
                    WHEN 12 THEN 176.4
                    WHEN 14 THEN 205.8
                    ELSE 0
                END) * 1000 / 1200) / pn.specific_weight * t_press_directive.ram_speed / 1000 * 60,
                1) AS work_speed2,
        ROUND((CASE t_press_directive.billet_size
                    WHEN 9 THEN (237 * 237 * 3.1415 / 4)
                    WHEN 12 THEN (312 * 312 * 3.1415 / 4)
                    WHEN 14 THEN (366 * 366 * 3.1415 / 4)
                    ELSE 0
                END) / (m_dies.hole * pn.cross_section_area) * t_press_directive.ram_speed * 60 / 1000,
                1) AS work_speed,
        t_press_directive.billet_temperature,
        t_press_directive.billet_taper_heating,
        t_press_directive.die_temperature,
        t_press_directive.die_heating_time,
        t_press_directive.stretch_ratio,
        CASE t_press_directive.cooling_type
            WHEN 1 THEN 'Air'
            WHEN 2 THEN 'Water'
            WHEN 3 THEN 'Mist/Shower'
        END AS cooling_type,
        t_press_directive.billet_size,
        m_bolster.bolster_name,
        CASE pn.aging_type_id
            WHEN 1 THEN 'O'
            WHEN 2 THEN 'T5'
            WHEN 3 THEN 'T6'
        END AS aging,
        'yyy' AS die_ring,
        t_press_directive.value_l,
        t100.max2 AS value_m,
        t100.max1 AS value_n,
        m_dies.hole,
        t_press_directive.press_machine,
        t_press_directive.initial,
        m_dies.die_note,
        CASE sub.h WHEN 0 THEN NULL ELSE sub.h END AS h,
        CASE sub.a WHEN 0 THEN NULL ELSE sub.a END AS a,
        CASE sub.b WHEN 0 THEN NULL ELSE sub.b END AS b,
        CASE sub.c WHEN 0 THEN NULL ELSE sub.c END AS c,
        CASE sub.d WHEN 0 THEN NULL ELSE sub.d END AS d,
        CASE sub.e WHEN 0 THEN NULL ELSE sub.e END AS e,
        CASE sub.f WHEN 0 THEN NULL ELSE sub.f END AS f,
        CASE sub.i WHEN 0 THEN NULL ELSE sub.i END AS i,
        CASE sub.k WHEN 0 THEN NULL ELSE sub.k END AS k,
        CASE sub.end WHEN 0 THEN NULL ELSE sub.end END AS end,
        t_press_plan.note AS plan_note,
        m_dies_diamater.die_diamater
    FROM t_press_directive
    LEFT JOIN m_dies ON t_press_directive.dies_id = m_dies.id
    LEFT JOIN m_dies_diamater ON m_dies.die_diamater_id = m_dies_diamater.id
    LEFT JOIN m_pressing_type ON t_press_directive.pressing_type_id = m_pressing_type.id
    LEFT JOIN m_bolster ON m_dies.bolstar_id = m_bolster.id
    LEFT JOIN m_staff ON t_press_directive.incharge_person_id = m_staff.id
    LEFT JOIN m_nbn ON t_press_directive.nbn_id = m_nbn.id
    LEFT JOIN m_die_production_number_variants v ON v.id = t_press_directive.die_production_number_variant_id
    LEFT JOIN m_production_numbers pn ON pn.id = COALESCE(v.production_number_id, m_dies.production_number_id)
    LEFT JOIN m_production_numbers_sub sub ON sub.production_number_id = pn.id
    LEFT JOIN (
        SELECT
            dies_id, max1, max2
        FROM t_press_work_length_quantity
        LEFT JOIN t_press ON t_press.id = t_press_work_length_quantity.press_id
        LEFT JOIN (
            SELECT a1.press_id, max1, MAX(b.work_quantity) AS max2
            FROM (
                SELECT press_id, MAX(work_quantity) AS max1
                FROM t_press_work_length_quantity AS a
                GROUP BY press_id
            ) a1
            JOIN t_press_work_length_quantity AS b ON b.press_id = a1.press_id AND b.work_quantity != a1.max1
            GROUP BY a1.press_id, a1.max1
        ) ttb ON ttb.press_id = t_press_work_length_quantity.press_id
        WHERE max1 > 0
        GROUP BY t_press.dies_id
    ) t100 ON t100.dies_id = t_press_directive.dies_id
    LEFT JOIN t_press_plan ON t_press_directive.dies_id = t_press_plan.dies_id
        AND t_press_plan.plan_date = t_press_directive.plan_date_at
    WHERE t_press_directive.id = :targetId
");
$stmt->bindValue(":targetId", $targetId, PDO::PARAM_INT);
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
