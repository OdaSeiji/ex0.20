# プレス指示画面(Step 1)新規作成

- 作成日: 2026-09-07
- 対象システム: ex0.20 (`C:\xampp\htdocs\ex0.20`)
- ステータス: **実装完了・動作確認済み。本番PC未適用(要対応)**

## 1. 背景

[[Step 0/0.5]](`documents/die_production_number/2026-09-01_press_directive_length_step0.md`)で`m_production_number_variants`(品番コード+長さ)を作った。目的は、生産指示画面が長さ違いの品番コードを正しく扱えるようにすること。

ユーザーが整理した生産の流れ:
1. オーダーシート受注(`order_sheet.html`、既存)
2. **進捗を見ながらプレス指示書作成**(ex0.11の`MakingPressDirectiveV11.html`のみに存在 → 本Stepでex0.20へ新規作成)
3. 生産実績入力(`press_daily_report.html`、既存)

## 2. 調査で判明したこと

- `m_ordersheet`は`production_numbers_id`(品番、基準行)+`production_quantity`(数量)のみを持ち、金型や長さバリアントとは無関係。ただし`php/order_sheet/list.php`にカット数/NG数/OK数/梱包数/**残数**の進捗計算が既に実装済み(「進捗を見ながら」の実現手段として流用)。
- ex0.11の`MakingPressDirectiveV11.html`は金型ごとの押出条件を計画するだけの画面。**オーダーシートとの連携が無く、長さ・品番も選択項目ではない**(品番は選んだ金型から自動導出される表示専用値)。長さは画面上で計算表示されるだけで、`t_press_directive`には一度も保存されていなかった。
- `t_press`には既に`press_directive_id`列があり(11,847件中11,786件=99.5%に入力済み)、`t_press_directive`への参照として機能している。

## 3. 採用した設計

`t_press_directive`に2列追加し、新画面`press_directive.html`を作った。**別テーブルは作らない**(billet-charge・press_daily_report・die_progress診断が読んでいる既存の`t_press_directive`をそのまま拡張する方が、それらへの影響がゼロで済むため)。

### 3.1 DBスキーマ変更

```sql
ALTER TABLE t_press_directive
  ADD COLUMN ordersheet_id INT NULL AFTER dies_id,
  ADD COLUMN production_number_variant_id INT NULL AFTER ordersheet_id;

ALTER TABLE t_press_directive
  ADD CONSTRAINT fk_pd_ordersheet FOREIGN KEY (ordersheet_id) REFERENCES m_ordersheet(id),
  ADD CONSTRAINT fk_pd_variant FOREIGN KEY (production_number_variant_id) REFERENCES m_production_number_variants(id);
```

ローカル環境で実行・成功。両列ともNULL許容。**本番PCでも同じSQLを実行する必要あり(未実行)。**

### 3.2 長さの保存経路

```
t_press → (press_directive_id、既存) → t_press_directive → (production_number_variant_id、新規) → m_production_number_variants.length
```

`t_press`側への列追加は不要だった。「計画時にどの長さ品番を狙うか」を記録する仕組みを追加するだけで、実績側(日報/`t_press`)の長さの保存方法(`first_actual_length`実測値+`m_dies→m_production_numbers`フォールバック)には一切手を加えていない。

### 3.3 画面フロー(`press_directive.html`)

1. **オーダーシート選択**(`get_ordersheets.php`): 残数量>0のオーダーシートを納期順に一覧表示、検索可能
2. **長さ(品番バリアント)選択**(`get_variants.php`): 選択したオーダーシートの`production_numbers_id`に対する`m_production_number_variants`一覧。1件のみなら自動選択
3. **金型選択**(`get_dies.php`): 同じ`production_numbers_id`を持つ金型を候補表示(タイプアヘッド検索)
4. **過去の指示履歴**(`get_directive_history.php`): 選択した金型の過去`t_press_directive`(最新10件)。「編集」(そのレコードを更新)・「複製」(新規として保存)の2ボタン
5. **押出条件フォーム**: ex0.11の項目を踏襲(放電しろ・プレス種別・ラム速度・ビレット関連・型温度・型加熱時間・伸び率・担当者・サンプル位置l/m/n・nBn・設備・冷却方式・備考)。ex0.11の`fillReadData()`がDOM位置ベースの脆いマッピングだったため、ここはDOM idベースの明示マッピングに直した
6. **保存**(`save_directive.php`/`update_directive.php`): `t_press_directive`にINSERT/UPDATE(`ordersheet_id`・`production_number_variant_id`含む)

### 3.4 新規ファイル

- `press_directive.html`
- `php/press_directive/get_ordersheets.php`, `get_variants.php`, `get_dies.php`, `get_masters.php`, `get_directive_history.php`, `save_directive.php`, `update_directive.php`, `press_directive_common.php`
- `index.html`の「📦 生産管理」に導線追加

### 3.5 今回のスコープ外

- ex0.11の`bolster`項目(元々保存されておらず死んでいたフィールド)
- Excel/PDF印刷出力(`SelForExcelV5.php`/`SelForPrintPageV6.php`相当)
- `order_sheet.html`自体の変更(`production_numbers_id`はそのまま使用)
- 45件の`-ZZZ`複製金型問題への対応(Step 0.5の決定通り、既存挙動のまま)

## 4. 動作確認結果

Chrome操作で実施:
1. オーダーシート一覧表示(220件、約6秒で表示。`order_sheet/list.php`と同等の負荷、許容範囲と判断)
2. オーダーシート選択 → 長さ候補自動選択 → 金型検索・選択(候補が正しく絞り込まれることを確認)
3. 過去の指示履歴表示(5件)
4. フォーム入力 → 保存 → DBで`ordersheet_id`・`production_number_variant_id`が正しく記録されることを確認
5. `press_daily_report.html`の指示書ドロップダウン相当のクエリ(`dies_id`絞り込み)で新規指示が先頭に出ることを確認
6. `billet-charge/SelPressDirective.php`が明示列指定のため列追加の影響を受けないことをコードレビューで確認
7. 履歴から「編集」→ 値の復元 → 更新 → DB反映を確認

## 5. 本番PC適用手順(未実行・要対応)

```sql
ALTER TABLE t_press_directive
  ADD COLUMN ordersheet_id INT NULL AFTER dies_id,
  ADD COLUMN production_number_variant_id INT NULL AFTER ordersheet_id;

ALTER TABLE t_press_directive
  ADD CONSTRAINT fk_pd_ordersheet FOREIGN KEY (ordersheet_id) REFERENCES m_ordersheet(id),
  ADD CONSTRAINT fk_pd_variant FOREIGN KEY (production_number_variant_id) REFERENCES m_production_number_variants(id);
```

前提: `m_production_number_variants`テーブル(Step 0.5)が先に本番PCへ適用済みであること(2026-09-07に適用完了済み、詳細は`2026-09-01_press_directive_length_step0.md`参照)。

## 6. 今後の課題

- オーダーシート一覧取得(`get_ordersheets.php`)が約6秒かかる。件数が増えると遅くなる可能性があるため、必要であれば納期の範囲絞り込みなどの最適化を検討
- Step 2(9/1計画時点の想定): 実績(`press_daily_report.html`)に「指示通りだったか」を表示する機能。上記3.2の経路で計算可能なため、新規列追加は不要と判明済み。UIとしてどう見せるかは未着手
- Step 3(9/1計画時点の想定): QualityReportV7画面(品質評価)の移植は未着手
