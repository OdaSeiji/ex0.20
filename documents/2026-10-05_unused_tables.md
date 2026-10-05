# 2026-10-05 使っていないテーブルの調査（調べただけ。削除はしていない）

ローカル DB（本番 PC から取り寄せたもの）の 127 テーブルについて、件数・最新の日付（日付の列の最大）・
コードから参照されている回数（テーブル名が出てくる回数）を調べた。
コードの置き場所：ex0.20、exd11（ex0.11）、ex0.21、diereport、Downloads/plc_sampling（PLC のスクリプト）。documents は除く。

## 判断の目安

### A. 消してよさそう（どこからも使われていない・空）
- t_die_lifecycle_history（0件）
- t_extrusion_sampling（0件）
- t_imex_anod（0件）
- t_die_next_condition（0件。t_die_diagnosis への外部キーはあるが、コードからは使われていない）

### B. 中身はあるが、コードから使われていない（中身の確認が必要）
- m_die_process_steps（5件、2026-04）
- t_fixed_assets（852件、2026-04）：固定資産の一覧？ 手で取り込んだもの？
- t_raspi_temp（6,687件、2025-06 で止まっている）：Raspberry Pi の温度記録？ 書き込むプログラムが別の PC にある可能性
- m_die_lifecycle_status（8件）は m_dies.die_lifecycle_status_id から外部キーで参照されているので残す

### C. ex0.11 だけが使っていて、更新が止まっている（ex0.11 で今も使う画面があるか確認）
- t_error（2022-11）、t_export（2022-02）、t_schedule（2022-12）、t_plc_web（2025-01。今は t_plc_web_log）、t_extrusion_log（2025-07）
- t_test・x_test（テスト用と思われる）、t_temp
- 0件：t_pack_plan、t_machine_maintenance、m_machine_category_1_id、m_machine_category_2_id、m_machine_maintenance_work

### D. phpMyAdmin の設定用（pma__ で始まる 20 テーブル）
phpMyAdmin 自身が使うので残す（exd11 の参照は、phpMyAdmin の SQL ファイルなどに名前が出てくるため）

### E. 移行が終わったら消す
- t_checkbillet_bak_20261003（6N01 → 6N01A のバックアップ）

### 気づいたこと
- t_washing_tank（洗浄タンク）は 2026-10-01 まで更新されている → ex0.11 の WashingTank の画面は今も使われている
- ex0.11 だけが使っていて、今も更新されているもの：t_hardness、t_import、t_packing_worker、t_maintenance_record、t_washing_tank など（ex0.20 へ移す候補）

## 全テーブル

| テーブル | 件数 | 最新の日付 | ex0.20 | exd11 | ex0.21 | diereport | PLC | 外部キーで参照 |
|---|---|---|---|---|---|---|---|---|
| m_aging_type | 3 |  | 6 | 45 | 6 | 0 | 0 | 1 |
| m_billet_material | 4 |  | 6 | 103 | 6 | 0 | 0 | 1 |
| m_billet_size | 3 |  | 3 | 46 | 1 | 0 | 0 | 1 |
| m_bolster | 13 | 2023-06-19 | 10 | 110 | 7 | 0 | 0 | 2 |
| m_code | 56 |  | 0 | 69 | 0 | 0 | 0 | 1 |
| m_cooling_type | 4 |  | 0 | 3 | 0 | 0 | 0 | 0 |
| m_dies | 1274 | 2026-10-01 | 218 | 1428 | 146 | 0 | 0 | 14 |
| m_dies_diamater | 10 |  | 15 | 117 | 8 | 0 | 0 | 1 |
| m_die_conditions | 3 |  | 7 | 0 | 0 | 0 | 0 | 1 |
| m_die_lifecycle_status | 8 |  | 0 | 0 | 0 | 0 | 0 | 1 |
| m_die_process_steps | 5 | 2026-04-13 | 0 | 0 | 0 | 0 | 0 | 0 |
| m_die_production_number_variants | 1277 | 2026-09-30 | 23 | 0 | 0 | 0 | 0 | 2 |
| m_die_status | 13 |  | 11 | 78 | 9 | 0 | 0 | 1 |
| m_equipment_status | 4 |  | 0 | 13 | 0 | 0 | 0 | 0 |
| m_error_code | 23 | 2022-05-17 | 0 | 57 | 0 | 0 | 0 | 1 |
| m_line | 4 |  | 0 | 115 | 0 | 0 | 0 | 1 |
| m_line_production | 8 |  | 0 | 13 | 0 | 0 | 0 | 0 |
| m_machine | 20 |  | 0 | 111 | 0 | 0 | 0 | 1 |
| m_machine_category_1_id | 0 |  | 0 | 36 | 0 | 0 | 0 | 2 |
| m_machine_category_2_id | 0 |  | 0 | 24 | 0 | 0 | 0 | 1 |
| m_machine_maintenance_work | 0 |  | 0 | 24 | 0 | 0 | 0 | 1 |
| m_maintenance_machine | 60 |  | 0 | 14 | 0 | 0 | 0 | 0 |
| m_measurement_data | 342 | 2025-11-11 | 0 | 32 | 0 | 0 | 0 | 0 |
| m_measurement_position | 4 |  | 0 | 3 | 0 | 0 | 0 | 1 |
| m_nbn | 9 |  | 10 | 89 | 3 | 0 | 0 | 1 |
| m_ordersheet | 4036 | 2026-09-30 | 36 | 709 | 32 | 0 | 0 | 3 |
| m_part_position | 48 | 2024-02-06 | 0 | 86 | 0 | 0 | 0 | 1 |
| m_pressing_type | 3 |  | 39 | 402 | 22 | 0 | 0 | 3 |
| m_press_stop_code | 7 |  | 4 | 142 | 4 | 0 | 0 | 1 |
| m_press_time_standard | 4 | 2026-10-05 | 3 | 0 | 0 | 0 | 0 | 0 |
| m_production_numbers | 788 | 2026-10-03 | 138 | 926 | 104 | 0 | 0 | 9 |
| m_production_numbers_category1 | 84 | 2026-06-29 | 17 | 66 | 17 | 0 | 0 | 1 |
| m_production_numbers_category2 | 171 | 2026-06-29 | 32 | 83 | 32 | 0 | 0 | 1 |
| m_production_numbers_sub | 412 |  | 1 | 161 | 0 | 0 | 0 | 0 |
| m_quality_code | 28 | 2021-06-11 | 6 | 756 | 0 | 0 | 0 | 1 |
| m_quality_code_check_process | 4 |  | 1 | 42 | 0 | 0 | 0 | 1 |
| m_staff | 90 | 2026-09-28 | 69 | 405 | 52 | 0 | 0 | 17 |
| m_staff_position | 2 |  | 0 | 38 | 0 | 0 | 0 | 1 |
| m_title_name | 40 |  | 0 | 26 | 0 | 0 | 0 | 0 |
| pma__bookmark | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__central_columns | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__column_info | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__designer_settings | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__export_templates | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__favorite | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__history | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__navigationhiding | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__pdf_pages | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__recent | 1 |  | 0 | 48 | 0 | 0 | 0 | 0 |
| pma__relation | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__savedsearches | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__table_coords | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__table_info | 0 |  | 0 | 45 | 0 | 0 | 0 | 0 |
| pma__table_uiprefs | 0 |  | 0 | 45 | 0 | 0 | 0 | 0 |
| pma__tracking | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__userconfig | 1 | 2021-09-25 | 0 | 48 | 0 | 0 | 0 | 0 |
| pma__usergroups | 0 |  | 0 | 42 | 0 | 0 | 0 | 0 |
| pma__users | 0 |  | 0 | 44 | 0 | 0 | 0 | 0 |
| t_aging | 3652 | 2025-12-25 | 1 | 110 | 0 | 0 | 0 | 1 |
| t_bundle | 22241 | 2026-10-01 | 5 | 148 | 3 | 0 | 0 | 0 |
| t_checkbillet | 1990 | 2026-09-19 | 9 | 118 | 0 | 0 | 0 | 0 |
| t_checkbillet_bak_20261003 | 1990 |  | 0 | 0 | 0 | 0 | 0 | 0 |
| t_cut_press | 5158 | 2026-10-01 | 3 | 28 | 3 | 0 | 0 | 0 |
| t_dies_import_tmp | 121 | 2026-09-15 | 8 | 0 | 7 | 0 | 0 | 0 |
| t_dies_status | 21312 | 2026-10-01 | 63 | 409 | 60 | 0 | 0 | 1 |
| t_dies_status_filename | 24 | 2026-04-09 | 13 | 0 | 13 | 0 | 0 | 0 |
| t_die_attachment | 1126 | 2026-10-01 | 23 | 0 | 23 | 0 | 0 | 0 |
| t_die_condition_history | 150 | 2026-09-26 | 3 | 0 | 0 | 0 | 0 | 0 |
| t_die_diagnosis | 281 | 2026-09-30 | 30 | 0 | 27 | 0 | 0 | 3 |
| t_die_fix | 150 | 2026-10-09 | 16 | 0 | 14 | 0 | 0 | 1 |
| t_die_handover | 1290 | 2026-10-01 | 29 | 0 | 13 | 0 | 0 | 0 |
| t_die_handover_progress | 1195 | 2026-10-14 | 15 | 0 | 8 | 0 | 0 | 0 |
| t_die_inspection | 293 | 2026-10-28 | 27 | 0 | 25 | 0 | 0 | 1 |
| t_die_issue | 117 | 2026-09-25 | 29 | 0 | 26 | 0 | 0 | 2 |
| t_die_lifecycle_history | 0 |  | 0 | 0 | 0 | 0 | 0 | 0 |
| t_die_next_condition | 0 |  | 0 | 0 | 0 | 0 | 0 | 0 |
| t_die_watch | 17 | 2026-09-11 | 6 | 0 | 6 | 0 | 0 | 1 |
| t_error | 1964 | 2022-11-15 | 0 | 60 | 0 | 0 | 0 | 0 |
| t_etching | 216712 |  | 1 | 49 | 0 | 0 | 0 | 0 |
| t_export | 34 | 2022-02-25 | 0 | 91 | 0 | 0 | 0 | 0 |
| t_extrusion_log | 18161 | 2025-07-09 | 0 | 54 | 0 | 0 | 0 | 0 |
| t_extrusion_sampling | 0 |  | 0 | 0 | 0 | 0 | 0 | 0 |
| t_fixed_assets | 852 | 2026-04-20 | 0 | 0 | 0 | 0 | 0 | 0 |
| t_hardness | 207907 | 2026-10-01 | 0 | 54 | 0 | 0 | 0 | 0 |
| t_imex_anod | 0 |  | 0 | 0 | 0 | 0 | 0 | 0 |
| t_import | 7063 | 2026-10-01 | 0 | 131 | 0 | 0 | 0 | 0 |
| t_import_billet | 156 |  | 0 | 12 | 0 | 0 | 0 | 1 |
| t_machine_maintenance | 0 |  | 0 | 21 | 0 | 0 | 0 | 0 |
| t_machine_maintenance_attached_file | 681 |  | 0 | 26 | 0 | 0 | 0 | 0 |
| t_maintenance_file_after | 327 |  | 0 | 3 | 0 | 0 | 0 | 0 |
| t_maintenance_file_before | 388 |  | 0 | 3 | 0 | 0 | 0 | 0 |
| t_maintenance_history | 547 | 2025-09-25 | 0 | 112 | 0 | 0 | 0 | 1 |
| t_maintenance_record | 615 | 2026-09-30 | 0 | 93 | 0 | 0 | 0 | 3 |
| t_maintenance_time | 12 | 2026-01-20 | 0 | 4 | 0 | 0 | 0 | 0 |
| t_measurement | 193550 | 2026-10-01 | 2 | 48 | 0 | 0 | 0 | 0 |
| t_measurement_file | 121 |  | 0 | 8 | 0 | 0 | 0 | 0 |
| t_measurement_position | 136 | 2023-12-20 | 0 | 4 | 0 | 0 | 0 | 0 |
| t_nitriding | 1703 | 2026-10-01 | 10 | 88 | 9 | 0 | 0 | 0 |
| t_note_ng_quality | 1 | 2025-07-29 | 0 | 2 | 0 | 0 | 0 | 0 |
| t_packing | 8482 | 2026-10-01 | 3 | 132 | 0 | 0 | 0 | 3 |
| t_packing_box | 39926 | 2026-10-01 | 13 | 187 | 6 | 0 | 0 | 0 |
| t_packing_box_number | 17666 | 2026-09-28 | 5 | 138 | 8 | 0 | 0 | 1 |
| t_packing_worker | 12474 | 2026-10-01 | 0 | 37 | 0 | 0 | 0 | 0 |
| t_pack_plan | 0 |  | 0 | 21 | 0 | 0 | 0 | 0 |
| t_parts_import_tmp | 0 |  | 7 | 0 | 0 | 0 | 0 | 0 |
| t_plc_web | 35365 | 2025-01-07 | 0 | 29 | 0 | 0 | 0 | 0 |
| t_plc_web_log | 17803 | 2026-10-01 | 6 | 52 | 2 | 0 | 0 | 0 |
| t_press | 12310 | 2026-11-02 | 239 | 3230 | 132 | 0 | 0 | 17 |
| t_press_directive | 14894 | 2026-10-02 | 101 | 841 | 55 | 0 | 0 | 0 |
| t_press_plan | 12889 | 2026-10-06 | 15 | 328 | 0 | 0 | 0 | 0 |
| t_press_quality | 31794 | 2026-10-01 | 26 | 866 | 5 | 0 | 0 | 0 |
| t_press_ram_log | 645374 | 2026-10-01 | 12 | 0 | 0 | 0 | 0 | 0 |
| t_press_schedule_day | 0 |  | 3 | 0 | 0 | 0 | 0 | 0 |
| t_press_staff | 7 | 2026-10-01 | 5 | 0 | 0 | 0 | 0 | 0 |
| t_press_sub | 14490 |  | 6 | 157 | 0 | 0 | 0 | 0 |
| t_press_sub_data | 14 |  | 0 | 5 | 0 | 0 | 0 | 0 |
| t_press_work_length_quantity | 108733 |  | 13 | 336 | 3 | 0 | 0 | 0 |
| t_pull_press | 4415 | 2026-11-26 | 3 | 28 | 3 | 0 | 0 | 0 |
| t_raspi_temp | 6687 | 2025-06-18 | 0 | 0 | 0 | 0 | 0 | 0 |
| t_schedule | 267 | 2022-12-04 | 0 | 89 | 0 | 0 | 0 | 0 |
| t_temp | 48 |  | 0 | 25 | 0 | 0 | 0 | 0 |
| t_test | 83 | 2025-01-07 | 0 | 24 | 0 | 0 | 0 | 0 |
| t_time_press | 7648 | 2026-10-01 | 5 | 75 | 3 | 0 | 0 | 0 |
| t_using_aging_rack | 30849 |  | 47 | 581 | 18 | 0 | 0 | 3 |
| t_washing_shot | 75 | 2026-07-21 | 0 | 35 | 0 | 0 | 0 | 0 |
| t_washing_tank | 277 | 2026-10-01 | 0 | 45 | 0 | 0 | 0 | 0 |
| x_test | 72 |  | 0 | 24 | 0 | 0 | 0 | 0 |
