# t_pressへのproduction_number_variant_id追加方針（実装保留）

- 作成日: 2026-09-19
- 対象システム: ex0.20 (`C:\xampp\htdocs\ex0.20`)
- ステータス: **方針決定のみ。実装は保留（ブロッカーあり、下記5参照）**

## 1. 背景

`press_directive.html`（Step1）の役割を確認する中で、「金型に紐づいた品番情報（`m_dies→m_production_numbers`）とは異なる、実際に作った長さ・品番（`production_length`）を記録したい」という要望を再確認した。

`m_production_number_variants`（Step0.5で導入済み、1品番につき複数の長さバリアントを持てるテーブル）が既にこの情報を持っているが、それを**実績側（`t_press`）でどう参照するか**が論点。

## 2. 過去の決定との相違点（重要）

`documents/die_production_number/2026-09-07_press_directive_step1.md`の3.2節では、以下の経路でJOINして引けば十分であり、**t_press側への列追加は不要**と結論していた:

```
t_press → (press_directive_id、既存) → t_press_directive → (production_number_variant_id) → m_production_number_variants.length
```

根拠は「`t_press.press_directive_id`が11,847件中11,786件（99.5%）に入力済み」だったこと。

**今回、この結論を覆し、`t_press`に`production_number_variant_id`を直接持たせる方針に変更する。** 理由:

1. `first_actual_length`と同じ設計原則（実績側の計算・レポートは常に`t_press`単体で完結させ、`t_press_directive`へのJOINに依存しない）に揃えたい
2. 99.5%であって100%ではない。指示書を経由しないプレス（試押など）や、残り0.5%の欠損レコードでは経路が切れる
3. バリアントは長さだけでなく専用の品番コード文字列も持つ（例: `C2Q63-04-J0782AK`）ため、IDで持たせておけば将来的に「長さ」以外の情報（専用品番コードそのもの）もレポート側で直接引ける

つまり、計画（`t_press_directive`）と実績（`t_press`）の両方に同じ列を持たせ、指示書適用時にコピーする、という考え方（`billet_size`・`billet_length`・`ram_speed`・`stretch_ratio`と同じパターン）に統一する。

## 3. `m_production_number_variants`の調査結果（2026-09-19時点）

| 項目 | 値 |
|---|---|
| 総件数 | 751件 |
| `m_production_numbers`総件数 | 751件（現時点で完全1対1） |
| 1品番あたりのバリアント数 | 全品番ちょうど1件（まだ実際には1対多に分岐していない） |
| `is_default` | 751件すべて`1` |
| バリアントの`production_number`文字列 | 全件マスタ`m_production_numbers.production_number`と完全一致 |
| バリアントの`length`とマスタ`production_length`の差異 | 3件のみ（軽微な差異、原因未調査） |

→ 現時点では実質1対1のミラーなので、`t_press`への一括バックフィル（金型→品番→バリアントの経路が曖昧さなく一意に決まる）は**今ならデータ的に安全に実施できる**。将来1対多に分岐が進むと、この単純な逆引きはできなくなる点に注意。

## 4. 実装予定の内容（未着手）

1. `t_press`に`production_number_variant_id`列を追加（NULL許容、`m_production_number_variants(id)`への外部キー）
2. 既存`t_press`全レコードへの一括バックフィル（3節の通り、現時点なら一意に決まる）
3. `press_daily_report.html`の`applyDirective()`に、他の項目（`billet_size`等）と同様の自動転記処理を追加
4. 良品量(t)換算など、必要な箇所のロジックを`production_number_variant_id`経由でも対応できるよう調整（`first_actual_length`優先、次点で`m_production_numbers.production_length`、という既存フォールバック順の中にどう組み込むかは実装時に検討）

## 5. ブロッカー（重要・実装着手条件）

**この実装は、`press_daily_report.html`（ex0.20）が実際に現場の日報入力で使われるようになってから着手する。**

現時点（2026-09-19）では、ユーザーは日報入力に**旧ex0.11のページ（`DailyReportV30`相当）をまだ使用しており、ex0.20の`press_daily_report.html`は本番の実績入力経路として使われていない**。この状態で`press_daily_report.html`側だけに自動転記ロジックを実装しても、実際の日報データがそちらを経由しない限り`t_press.production_number_variant_id`は埋まらず、意味を持たない。

→ **次のアクション**: `press_daily_report.html`への移行状況を確認し、実際に運用で使われ始めたタイミングで本Stepに着手する。

## 6. 関連ドキュメント

- `documents/die_production_number/2026-08-29_intermediate_table_plan.md`（中間テーブル方式の検討・不採用）
- `documents/die_production_number/2026-09-01_press_directive_length_step0.md`（Step0/0.5、`m_production_number_variants`導入）
- `documents/die_production_number/2026-09-07_press_directive_step1.md`（Step1、`press_directive.html`新規作成。3.2節が今回覆した過去の結論）
