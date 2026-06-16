# Changelog 2026-06-16

## 1. 部品登録機能（die_part_import.html）の大幅改修

### テーブル新設
- `t_parts_import_tmp` を新規作成（`t_dies_import_tmp` から分離）
  - カラム: `id`, `die_number`, `die_id`, `note2`, `import_error`, `created_at`

### CSVフォーマット変更
- 旧: `die_number, is_accessory_item_flag`
- 新: `die_number, note2`（`is_accessory_item_flag` は常に1のため不要）

### 重複チェックロジック逆転
- 旧: `m_dies` に**ある**場合はスキップ（新規金型として登録）
- 新: `m_dies` に**ない**場合はエラー拒否（部品は金型が先に存在必須）

### 新規PHPファイル
- `upload_parts_csv.php`: ロジック刷新（`t_parts_import_tmp` へ保存、`die_id` 解決）
- `get_parts_import_list.php`: `EXISTS` サブクエリで `dup_warning` を判定、既存レコードを別配列 `existing` で返す
- `transfer_to_parts.php`: `t_die_handover` への INSERT のみ（`m_dies` INSERT なし、`t_die_handover_progress` なし）、転送成功後は `t_parts_import_tmp` から DELETE
- `delete_parts_import_row.php`: `t_parts_import_tmp` 専用削除

### 画面改善
- インポートテーブルと既存引き継ぎレコードテーブルを**分離表示**（JOINによる行重複問題を解消）
- 既存レコードは `t_die_handover` に該当 `die_id` がある場合のみ紫枠テーブルで表示
- インポート行クリックで対応する既存レコード行を**連動ハイライト**（オレンジ枠）
- 転送済レコードは即 DELETE されるためフィルター不要→リストを常時「全件（未処理のみ）」に簡素化

---

## 2. index.html「修理報告未完了」カード修正

### 問題
- `get_progress_summary.php` の `fix_report` カウントが `die_progress_list.html` の「修理報告待ち」件数と不一致

### 原因
1. `f.plan_fix_date IS NOT NULL` 条件が余分に付いていた
2. `f.actual_fix_reported_at`（存在しないカラム）を参照していた（正: `f.actual_fix_date`）

### 修正内容
- `fix_report` のみ別サブクエリで全期間集計（他の4カードは90日制限を維持）
- サブクエリに `LIMIT 200` を適用し `die_progress_list.html` と同じ範囲に統一
- 使用カラムを `f.actual_fix_date` に修正

---

## 3. die_fix_plan.html 改修

### 修理完了予定日フィールド追加
- `t_die_fix` テーブルに `plan_completion_date DATE` カラムを追加（`plan_fix_date` の直後）
- 入力欄を「修理予定日」の下に追加
- `save_fix_plan.php` の INSERT / UPDATE に対応
- 承認モード時は `disabled` に設定

### 修理予定日のデフォルト値
- ページロード時に今日の日付をデフォルトで設定

### 多言語対応（ベトナム語）実装
- `setLang()` がlocalStorage保存のみで画面未更新だった問題を修正
- 辞書（`dict.ja` / `dict.vi`）を追加し、全ラベル・ボタン・アラートを多言語対応
- `applyLang()` でページロード時と言語切替時に一括適用

---

## 4. die_progress_list.html 修正

### 列幅調整
- 「重点管理」「優先度」列を 68px → 54px（約20%削減）

### 修理完了予定日列を追加
- `get_progress_list.php` に `f.plan_completion_date` を追加
- 「修理計画」列の右に「完了予定」列（46px）を新設
- 日本語: `mm-dd`、ベトナム語: `dd-mm` 形式で表示
- データなしの場合は `—`（グレー）

---

## 本番DB適用SQL

```sql
-- 1. t_parts_import_tmp 新規作成
CREATE TABLE t_parts_import_tmp (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  die_number   VARCHAR(50)  NOT NULL,
  die_id       INT          NOT NULL,
  note2        VARCHAR(255) DEFAULT NULL,
  import_error VARCHAR(255) DEFAULT NULL,
  created_at   DATE         NOT NULL
);

-- 2. t_die_fix に修理完了予定日カラム追加
ALTER TABLE t_die_fix
  ADD COLUMN plan_completion_date DATE DEFAULT NULL
  AFTER plan_fix_date;
```
