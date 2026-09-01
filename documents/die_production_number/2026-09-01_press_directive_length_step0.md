# プレス指示「定尺長さ選択」対応 — Step 0(品番ごとの複数定尺長さ管理)

- 作成日: 2026-09-01
- 対象システム: ex0.20 (`C:\xampp\htdocs\ex0.20`)
- ステータス: **Step 0 実装完了・動作確認済み**

## 1. 背景

[[型番-品番1対多化の検討]](`documents/die_production_number/2026-08-29_intermediate_table_plan.md`)で、「1つの金型で長さ違いの製品を複数扱いたい」という要望の実態を整理した結果、型番⇔品番のマスタ構造は変更不要で、対応は「プレス指示(計画)に、その回の長さを記録する」ことに絞られると結論した。

2026-09-01、業務フローをさらに詳しく確認し、実際に必要な改造は次の3ステップだと整理した(1→2→3の順で着手):

1. **プレス指示画面**(ex0.11 `MakingPressDirectiveV11.html`相当)をex0.20へ新規移植し、長さ入力を「あらかじめ登録された定尺候補から選ぶ」方式にする
2. **daily report(実績、`press_daily_report.html`)** に「指示値通りだったか」の入力欄を追加し、`t_press`に列追加
3. **QualityReportV7画面**(品質評価)の移植

1で「定尺候補から選ぶ」を実現するには、その候補リスト自体を管理できる必要がある。調査の結果、ex0.11のプレス指示画面には長さを保存する仕組みが元々無く(画面上の計算値はDB未保存)、`m_production_numbers.production_length`は品番につき1つの固定値しか持てない。そこで1の前段階として、本ドキュメントの**Step 0**を実施した。

## 2. Step 0 実装内容

### 2.1 新規テーブル

```sql
CREATE TABLE m_production_number_lengths (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  production_number_id  INT NOT NULL,
  length                DECIMAL(10,3) NOT NULL,
  is_default            TINYINT(1) NOT NULL DEFAULT 0,
  created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mpnl_pn_length (production_number_id, length),
  KEY idx_mpnl_pn (production_number_id),
  CONSTRAINT fk_mpnl_pn FOREIGN KEY (production_number_id)
    REFERENCES m_production_numbers(id) ON DELETE CASCADE
);

INSERT INTO m_production_number_lengths (production_number_id, length, is_default)
SELECT id, production_length, 1
FROM m_production_numbers
WHERE production_length IS NOT NULL;
```

ローカル環境で実行・成功。検証: `m_production_numbers`の`production_length IS NOT NULL`件数と、本テーブルの件数が751件で一致することを確認済み。**本番PCでも同じSQLを実行する必要あり。**

`m_production_numbers.production_length`列は変更しない。「既定(デフォルト)の長さ」を表す列として使い続け、`get_machine_monthly.php`の良品量(t)換算フォールバックやCSVインポートはノータッチで動く。新テーブルは追加の「候補一覧」で、保存のたびに次の内容で全置換する:
1. `production_length`の値を`is_default=1`の1行として必ず入れる
2. ユーザーが追加した候補値を`is_default=0`で入れる

### 2.2 UI

`production_number.html`の編集フォームに「その他の定尺候補 (m)」欄を追加(`fLength`直下)。`press_daily_report.html`の定尺・数量登録欄と同じ「テーブル+追加行フォーム」パターン。追加・削除・保存・編集復元の一連の動作を確認済み。

### 2.3 変更・新規PHPファイル

- 新規: `php/ProductionNumber/production_number_common.php`(`saveLengthOptions()`/`parseLengthOptions()`)
- 新規: `php/ProductionNumber/SelLengthOptions.php`
- 修正: `InsInputData3.php` / `UpdateSummaryV3.php`(`db.php`+トランザクション化、長さ候補の保存を追加)
- 修正: `DelSummary.php` / `SelSelSummaryV3.php`(`db.php`化のみ)
- 修正: `bulk_insert_pn.php`(CSVインポート時もis_default行を同期)

## 3. 動作確認結果

Chrome操作で実施:
1. 既存品番(`INI-398-1-112-D`、製品長3.000)を編集画面で開く → 「その他の定尺候補」欄は空("登録データなし")、`fLength`は3.000のまま → OK
2. 候補として2.500・4.000を追加して更新 → DBで`m_production_number_lengths`に(3.000, is_default=1)(2.500, is_default=0)(4.000, is_default=0)の3行が入ることを確認 → OK
3. 再度編集画面を開く → 2.500・4.000が復元される → OK
4. 候補行を✕ボタンで削除 → 保存 → DBの行が正しく置換される(delete-then-reinsert)ことを確認 → OK
5. `get_machine_monthly.php`の良品量計算に影響する`m_production_numbers.production_length`列自体は今回無変更 → 影響なし

### 3.1 副次的に発見した既存の不具合(今回のスコープ外・未修正)

`production_number.html`で、品番を編集画面で開いた直後にカテゴリ選択欄を触らずそのまま「更新する」を押すと、カテゴリ2が実際には選択済み(ハイライト表示)であるにもかかわらず「カテゴリを選択してください」という検証エラーで更新がブロックされることがある。長さ候補機能とは無関係に、今回のコード変更を一切加えていない状態でも再現した(対照実験で確認済み)。カテゴリ2の行を一度手動でクリックし直すと解消し、正常に更新できる。原因は`restoreCategories()`が`onCat1Click()`経由でカテゴリ2一覧を非同期に再取得・再描画する際のタイミングに関連すると推測されるが、未特定。別途対応が必要であれば改めて調査する。
