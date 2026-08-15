# Changelog 2026-08-15

## 1. machine_report.html 表記修正 ＋ 良品量(t)グラフ追加

### 表記修正
- 「押出量（t）」表記が実態と不一致だったため「ビレット投入量（t）」に修正（テーブル見出し・グラフ凡例・右軸ラベル、日本語/ベトナム語とも）
  - `extrusion_t` はビレット体積（`billet_size` × `billet_length` × `actual_billet_quantities`）から算出した値であり、押出後の量ではなくビレット投入量であるため

### 良品量(t)の折れ線グラフ追加
- `get_machine_monthly.php` に月別・設備別の良品量(t)集計を追加（JSON出力に `goodWeights` を追加）
- 計算式：
  ```
  良品量(t) = T_ok本数 × specific_weight(kg/m) × COALESCE(first_actual_length, production_length × 1000) / 1,000,000
  ```
  - `T_ok`（良品本数） = `t_using_aging_rack.work_quantity` − `t_press_quality.ng_quantities`（`t_using_aging_rack` 経由で紐付け）
  - 長さは `t_press.first_actual_length`（実測値, mm）を優先し、NULLの場合は `m_production_numbers.production_length`（規定値, m）× 1000 で補完
    - `first_actual_length` は2023年以降ほぼ全件入力済みだが、2021年は98.7%、2022年は20.9%がNULLのため補完が必要
- `machine_report.html` のグラフに緑色の折れ線として追加（ビレット投入量と同じ右軸・トン単位で重ねて表示）

---

## 2. die_startup_report.html「金型到着数」の分離

### 問題
- 「金型到着数」が `t_die_handover_progress.arrival_at` を単純カウントしていたが、金型本体の初回到着と、既存金型に対する後日の部品到着（`is_accessory_item_flag`）を区別できていなかった

### 修正内容
- データソースを `t_die_handover.die_arrived_at` に変更し、`is_accessory_item_flag` で以下2指標に分離集計
  - `arrival`（金型到着数）：`IFNULL(is_accessory_item_flag, 0) = 0`（金型本体）
  - `parts_arrival`（金型部品到着数）：`is_accessory_item_flag = 1`（部品）
- `die_startup_report.html` の表・グラフ両方に「金型部品到着数」を追加（日本語/ベトナム語とも）
- 「移管数－到着数」の差分計算は本体到着数（`arrival`）を引き続き使用

---

## 3. order_sheet.html 品番照合バグ修正

### 問題
- Excelインポート時、実在する品番でも「✖ 品番不明」と判定されるケースがあった

### 原因
- `php/order_sheet/production_numbers.php` がキーワード無し呼び出し時に `LIMIT 50` を適用していたため、`m_production_numbers`（全749件）のうちアルファベット順で先頭50件しかクライアントに返っておらず、それ以外の品番はすべて未一致になっていた

### 修正内容
- キーワード無し時の `LIMIT 50` を撤廃し、全件を返すよう修正
