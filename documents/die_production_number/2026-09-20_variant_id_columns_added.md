# t_press / t_press_directiveへのdie_production_number_variant_id追加、press_directive.html再構築に向けた方針確定

- 作成日: 2026-09-20
- 対象システム: ex0.20 (`C:\xampp\htdocs\ex0.20`)
- ステータス: **ローカルDB・本番PCともスキーマ変更・バックフィル実行済み(2026-09-20完了)。press_directive.htmlのコード改修は未着手。**

## 1. 背景

前日(2026-09-19)に`documents/die_production_number/2026-09-19_t_press_variant_plan.md`と`2026-09-19_die_production_number_many_to_many.md`の2つの方針書を作成した。今回、両ドキュメントの内容を突き合わせて再確認し、一部方針を修正・確定した。

## 2. 前日方針からの修正点

**修正①: `t_press.production_number_variant_id`のブロッカーを撤回。**

`2026-09-19_t_press_variant_plan.md`5節は「`press_daily_report.html`が現場で実際に使われ始めてから着手する」としていたが、これを撤回する。理由: 列の追加とバックフィル自体は、どちらの画面(旧ex0.11 or `press_daily_report.html`)がデータ入力に使われているかとは独立した作業であり、今すぐ実施できる。今後、新規レコードの自動転記処理(`press_daily_report.html`側の実装)は別途、現場採用のタイミングで対応する。

**修正②: 列名を`production_number_variant_id`から`die_production_number_variant_id`に変更。**

理由: 参照先テーブルが`m_production_number_variants`から`m_die_production_number_variants`に改名済みのため、FK列名もテーブル名に合わせる。他列(`pressing_type_id`等)の命名慣習(単数形+`_id`)より、参照先テーブル名との対応関係を優先した。

**確定事項(変更なし): `t_press_directive`に`ordersheet_id`は追加しない。** `2026-09-19_die_production_number_many_to_many.md`6節4項の方針を維持する。

## 3. 実施したスキーマ変更(2026-09-20、ローカルのみ・本番PC未適用)

```sql
ALTER TABLE t_press_directive
  ADD COLUMN die_production_number_variant_id INT NULL AFTER dies_id,
  ADD CONSTRAINT fk_directive_die_pn_variant FOREIGN KEY (die_production_number_variant_id)
    REFERENCES m_die_production_number_variants(id) ON DELETE SET NULL;

ALTER TABLE t_press
  ADD COLUMN die_production_number_variant_id INT NULL AFTER dies_id,
  ADD CONSTRAINT fk_press_die_pn_variant FOREIGN KEY (die_production_number_variant_id)
    REFERENCES m_die_production_number_variants(id) ON DELETE SET NULL;
```

`ON DELETE SET NULL`を選択した理由: `die_production_number.html`の`remove_link.php`で紐づけ行が削除された際、過去の実績・指示レコードが参照エラーでDELETE自体をブロックしないようにするため(削除時は単に参照がNULLに戻るだけにする)。

### バックフィル(t_pressの既存データ、紐づく品番が1つだけの金型のみ対象)

```sql
UPDATE t_press tp
JOIN (
  SELECT die_id, id AS variant_id
  FROM m_die_production_number_variants
  WHERE die_id IN (
    SELECT die_id FROM m_die_production_number_variants GROUP BY die_id HAVING COUNT(*) = 1
  )
) v ON tp.dies_id = v.die_id
SET tp.die_production_number_variant_id = v.variant_id
WHERE tp.die_production_number_variant_id IS NULL;
```

**結果(2026-09-20時点、ローカル):** `t_press`全12,195件中12,137件をバックフィル、58件が未設定のまま残った。未設定になるのは以下のいずれか:
- その金型がまだ`m_die_production_number_variants`に紐づけ登録されていない
- その金型が複数の品番に紐づいており(下記7件)、`dies_id`だけでは一意に決まらないため意図的にスキップ

**複数品番に紐づく金型(2026-09-20時点、7件、`die_production_number.html`経由で追加登録されたもの):**

| die_id | 紐づく品番数 |
|---|---|
| 116 | 2 |
| 192 | 2 |
| 193 | 2 |
| 731 | 2 |
| 796 | 2 |
| 797 | 2 |
| 1143 | 2 |

これらの金型に紐づく`t_press`の過去レコードは、どちらの品番を実際に使ったか自動判定できないため、`die_production_number_variant_id`はNULLのまま残す。将来、実績入力画面(`press_daily_report.html`)側で選択させるか、個別に手動特定する必要がある(未対応・別スコープ)。

**結果(2026-09-20時点、本番PC):** バックフィルUPDATEは12,197件(`Rows matched: 12197 Changed: 12197`)。ローカルの12,137件より60件多いが、`t_press`は稼働中の実績ログテーブルであり本番は生きたデータが日々増え続けている一方ローカルは作業用スナップショットのため、この差は想定通り(スキーマ不整合ではない)。

## 4. 未実施(次のアクション)

1. ~~本番PC適用~~ → **2026-09-20完了**(上記の通り)
2. **`press_directive.html`の改修**(9/19より前に作られたコードのため、現行の`m_die_production_number_variants`スキーマと不整合が生じている):
   - **バグ**: `php/press_directive/get_variants.php`が、9/19の簡素化で削除済みの列(`production_number`, `length`, `is_default`)を今もSELECTしており、呼び出すとSQLエラーになる
   - **設計の古さ**: Step3「定尺(長さ)選択」は「1品番内の複数長さバリアント」という旧モデル(Step0.5当時)のUIのまま。新モデルでは「1金型が複数品番に紐づき、各品番が固有の`production_length`を持つ」という考え方に変わっているため、UIごと作り直す必要がある
   - **Step1の見直し**: `php/press_directive/get_dies.php`が今も`m_dies.production_number_id`という旧・単一FKのみを参照しており、新しい多対多関係(`m_die_production_number_variants`)を見ていない
   - **保存先の指針**: `die_production_number_variant_id`には`m_production_numbers.id`ではなく`m_die_production_number_variants.id`(金型×品番の紐づけ行のid)を保存する。長さは`production_number_id`経由で`m_production_numbers.production_length`を引く
   - **削除候補**: Step3の「受注番号選択(オーダーシート)」UI一式(`get_ordersheets.php`呼び出し・`selectOrdersheet()`等)は、`ordersheet_id`を保存しない方針が確定した以上、死んだ機能。2026-09-07版の名残であり削除する
   - **履歴表示の修正**: `get_directive_history.php`のSELECTと画面側の表示列(現在は受注番号・品番(長さ)が常に"-"表示)を、新しい`die_production_number_variant_id`経由の情報に置き換える
3. `press_daily_report.html`側の自動転記処理(`applyDirective()`への追加)は、現場が`press_daily_report.html`を使い始めてから着手(この部分のみブロッカーは維持)

## 5. 関連ドキュメント

- `documents/die_production_number/2026-09-19_t_press_variant_plan.md`(前日方針、5節のブロッカーを本ドキュメントで撤回)
- `documents/die_production_number/2026-09-19_die_production_number_many_to_many.md`(前日方針、4節・6節の列名を本ドキュメントで`die_production_number_variant_id`に確定)
- `documents/die_production_number/2026-09-07_press_directive_step1.md`(`press_directive.html`初版作成時のドキュメント、Step3の受注番号連携は今回破棄予定)
