# Changelog

---

## V6.11 — 2026-06-15

### 部品登録機能の追加

- **`die_part_import.html`（新規）**
  CSVから部品を一括登録する画面を新設。必要な列は `die_number` と `is_accessory_item_flag` の2列のみ。`is_accessory_item_flag = 1` の行のみ登録対象。

- **`index.html`**
  トップメニューに「📋 新規、金型、品番登録」アコーディオンを追加。「新規金型登録（die_import.html）」と「部品登録（die_part_import.html）」の2カードを収容。

- **`die_import.html`**
  タイトルに「部品登録を除く」を赤字で追記。

### handover_list.html の編集機能拡張

- **到着日を編集可能に変更**
  編集モードで到着日（`die_arrived_at`）が日付入力フィールドとして表示されるようになった（従来は表示のみ）。

- **CSVインポートボタンを不活性化**
  「📂 CSV インポート」ボタンを disabled に変更。

### その他の修正

- **`get_pending_dies.php`**
  `die_arrival.html` の表示条件に `t_die_handover.is_accessory_item_flag = 1` の金型も含めるよう変更。

- **`insert_die_handover.php`**
  `t_die_handover` への INSERT 時に `is_accessory_item_flag` を含めるよう修正。

---

## V6.10 — 2026-06-13

### 診断フェーズ移行機能の追加

- **`die_progress_list.html`**
  診断プレビューに「フェーズ移行」（`advance_condition`）の表示を追加。あり／なしをバッジで表示。

- **`handover_list.html`**
  CSVインポート形式を `die_name, is_accessory_item` の2列に変更。`is_accessory_item = 1` の行のみ登録対象。

- **`t_die_handover` カラム名変更**
  `die_arrival_at` → `die_arrived_at` に統一。

---

## V6.9 — 2026-06-12

### 金型コンディション管理機能の追加

- フェーズ管理（Trial → MPT → MP）の実装。
- `die_diagnosis.html` にフェーズ移行ボタンを追加。
- `die_issue_progress.html` を新設（Issue別の現在フェーズと停滞状況一覧）。
- `die_handover_progress.html` にフィルターバーを追加（5種類の待ち状態ボタン）。
- `die_arrival.html` に型番検索を追加。

---

## V6.8.2 — 2026-06-08

- 天気ウィジェットに北京を追加。
