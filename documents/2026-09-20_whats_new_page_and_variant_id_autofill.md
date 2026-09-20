# 2026-09-20 更新履歴ページの新設 と 実績保存時のdie_production_number_variant_id自動反映

## 1. press_daily_report.htmlの実績保存にdie_production_number_variant_idを自動反映

### 目的
1つの金型で複数の製品長さに対応できるようにするため。press_directive.html（押出指示書）で
どの製品長さ（品番）を狙うか決めた情報を、実績（t_press）側にも引き継ぐ必要があった。

### 内容
- `press_directive.html`で指示を作成すると、選択した製品長さの紐づけID
  （`m_die_production_number_variants.id`）が`t_press_directive.die_production_number_variant_id`
  に保存される。
- これまで`press_daily_report.html`で実績を保存する際、この値が`t_press`側にコピーされて
  いなかった（未実装）。
- `php/press_daily_report/press_common.php`に`resolveDieProductionNumberVariantId($pdo, $pressDirectiveId)`
  を追加。`press_directive_id`から対応する指示書の`die_production_number_variant_id`を引いて返す
  （指示書経由でない実績の場合はnull）。
- `pressColumns()`に`die_production_number_variant_id`を追加し、`bindPressValues()`でバインド。
- `save_press.php`（新規登録）・`update_press.php`（更新）の両方で、保存直前に
  `resolveDieProductionNumberVariantId()`を呼び出して`$p["die_production_number_variant_id"]`を
  セットしてから保存するよう変更。
- 既存データへの一括UPDATE（バックフィル）は実施せず、今後の新規保存・更新分から反映される。

### 対象ファイル
- `php/press_daily_report/press_common.php`
- `php/press_daily_report/save_press.php`
- `php/press_daily_report/update_press.php`

## 2. 更新履歴ページ（whats_new.html）の新設

### 目的
日々の機能追加・変更内容をユーザー向けに分かりやすくまとめ、該当ページへすぐ移動できるように
するため。

### 内容
- 新規ページ`whats_new.html`を作成。カード形式で各更新項目を「新設/変更」バッジ・日付・タイトル・
  目的（今回のe1項目のみ「目的」と「内容」を分離）・関連ファイルへのリンク付きで表示。
- 日本語／ベトナム語の多言語対応（他ページと同様の`dict{ja,vi}`+`data-key`方式）。
- 項目は日付の新しい順（2026-09-20 → 2026-09-19 → 2026-09-13）に並べ替え済み。
- 現在掲載中の項目：
  1. 押出日報の実績保存に、指示書の紐づけIDを自動反映（2026-09-20・変更）
  2. 押出指示書ページの新設（2026-09-20・新設）
  3. 金型－品番 紐づけページの追加（2026-09-19・新設）
  4. 固定資産登録日を金型移管日に自動反映（2026-09-19・変更）
  5. 押出日報入力画面の調整（2026-09-13・変更）
- `index.html`の右上（`.lang-switch`、ex0.21ボタンの左）に「📋 更新履歴」ボタンを追加し、
  `whats_new.html`へ遷移できるようにした。

### 対象ファイル
- `whats_new.html`（新規）
- `index.html`
