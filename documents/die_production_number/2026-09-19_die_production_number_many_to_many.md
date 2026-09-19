# 金型↔品番の多対多化、および長さ情報の二経路並立方針

- 作成日: 2026-09-19
- 対象システム: ex0.20 (`C:\xampp\htdocs\ex0.20`)
- ステータス: **方針決定のみ。実装は未着手（下記5節の実装ステップは何一つ実行していない）**

## 1. 背景

`press_directive.html`をex0.11ベースで作り直す（[[2026-09-19_t_press_variant_plan.md]]参照）過程で、「金型に紐づく品番情報とは別に、製品長さの指定が必要」という要件を再確認した。この長さ情報は`m_production_number_variants`（Step0.5で導入済み）から取得する必要があるが、その議論の中で以下の設計上の問いが出た：

> `m_production_number_variants`に`die_id`カラムを追加したら、`m_dies (多) ── m_production_number_variants ── (多) m_production_numbers`という多対多の関係を作れるか？

本ドキュメントは、この問いへの回答（Yes）と、それに伴う設計方針をまとめる。

## 2. 結論：`die_id`追加で多対多関係が成立する

```
m_dies (1) ──────< m_production_number_variants >────── (1) m_production_numbers
                         ├─ die_id            (新規追加予定)
                         ├─ production_number_id (既存)
                         ├─ length               (既存)
                         ├─ production_number    (既存。この組み合わせ専用のコード)
                         └─ is_default           (既存)
```

- 同じ`die_id`で`production_number_id`が異なる行を複数持てば「1金型が複数品番を作る」を表現できる
- 同じ`production_number_id`で`die_id`が異なる行を複数持てば「1品番を複数金型で作る」を表現できる（`-ZZZ`複製金型が本来解決したかった問題そのもの）
- 教科書的な多対多の中間テーブルとして機能する。`length`はその「金型×品番」の組み合わせに付随する追加属性

**副次的な発見：** この構造が最初からあれば、`-ZZZ`複製金型（1金型に複数品番を持たせるためだけに金型レコード自体を複製していた、45件確認済み。[[project_die_pn_1tomany]]参照）を作らずに済んでいた可能性が高い。既存の`-ZZZ`は引き続き既知の技術的負債として残すが（削除・統合は別スコープ、影響1,700件超のため見送り済み）、今後の新規データではこの問題を作らない設計になる。

## 3. 二重管理の扱い（重要な意思決定）

`m_dies.production_number_id`（既存の単一FK、10テーブル以上が依存）と、新しい`m_production_number_variants`経由の多対多関係が並立することになる。この二重管理について：

**方針：`m_dies.production_number_id`は当面そのまま残し、「その金型のデフォルト品番」として使い続ける。** 新しい多対多関係（`die_id`付き`m_production_number_variants`）は、既存の関係を置き換えるのではなく**追加のレイヤーとして並走**させる。

理由：
- 既存の10+8テーブルの集計・レポートを生かせる（後方互換性）
- システムを完全に新方式へ移行できるまでは、現行システムが問題なく動き続ける必要がある
- 将来、新方式への移行が十分進んだ段階で、`m_dies.production_number_id`の廃止を再検討する（今回はスコープ外、タイムラインも未定）

## 4. 長さ・品番情報の二経路並立（もう一つの並立）

上記とは別に、**t_press / t_press_directiveにおける「長さ」情報の取得経路**についても、新旧2つが並立することになる。

```
【旧経路・レガシー】既存の全レポート・良品量換算ロジックが使用中、今回一切変更しない
t_press.first_actual_length（実測値、優先）
  ↓ NULLの場合
m_dies.production_number_id → m_production_numbers.production_length（マスタの標準値で補完）

【新経路・今回の方針で追加】
t_press_directive.production_number_variant_id（今回追加する。詳細は下記5節）
t_press.production_number_variant_id（将来追加。press_daily_report.htmlの現場採用後まで実装保留。
                                        [[2026-09-19_t_press_variant_plan.md]]参照）
  ↓
m_production_number_variants.length（金型×品番の組み合わせごとの、より精密な長さ）
```

この2経路は**独立して並立**する。新経路が無い（NULLの）レコードは、これまで通り旧経路のフォールバックだけで動く。新経路は「このプレス（または指示）が具体的にどの金型×品番×長さの組み合わせを狙ったか」を、より正確に残すための追加情報という位置づけ。

なお、3節（金型↔品番の多対多化）と4節（長さ情報の二経路化）は**同じ`m_production_number_variants`テーブルを使うが、互いに独立した機能**である。3節（`die_id`追加）だけを先に実装し、4節（`t_press`/`t_press_directive`への`production_number_variant_id`追加）を後回しにする、あるいはその逆も可能。

## 5. テーブル名の見直し（検討中）

`m_production_number_variants`という名前は、Step0.5時点の「1つの品番内の長さバリアント」という役割には適していたが、3節の通り`die_id`を追加して金型↔品番の中間テーブルを兼ねるようになると、「die」の要素が名前に表れておらず分かりにくい。

**推奨改名案：`m_die_production_number_variants`**

- `m_`（マスタ）プレフィックスは維持（静的な参照データという性質は変わらないため。過去に不採用とした中間テーブル案`t_die_production_numbers`とは異なる）
- 改名の影響範囲（実コード、2026-09-19時点で確認済み）：
  - `php/press_directive/get_variants.php`
  - `php/ProductionNumber/production_number_common.php`
  - `php/ProductionNumber/SelVariantOptions.php`
  - `php/ProductionNumber/UpdateSummaryV3.php`
  - （4ファイルのみ。Step1/Step2でさらに依存箇所が増える前の今が改名の適期）

**この改名は未実行。** ユーザーの最終確認後に実施する。

## 6. 未実装のステップ（本ドキュメント作成時点で何一つ未着手）

1. `m_production_number_variants`のリネーム（→`m_die_production_number_variants`、5節）
2. リネーム後テーブルへの`die_id`カラム追加（NULL許容、`m_dies(id)`への外部キー）
3. 既存751行への`die_id`バックフィル（現状1対1のミラーなので、`m_dies.production_number_id`から一意に決まる）
4. `t_press_directive`への`production_number_variant_id`カラム追加（`ordersheet_id`は追加**しない**方針、[[2026-09-19_t_press_variant_plan.md]]の方針を一部修正）
5. `press_directive.html`の再構成（ex0.11ベース、金型選択起点。オーダーシート・残数量の概念は持たない）

## 7. 関連ドキュメント

- `documents/die_production_number/2026-08-29_intermediate_table_plan.md`（中間テーブル方式の最初の検討・不採用）
- `documents/die_production_number/2026-09-01_press_directive_length_step0.md`（Step0/0.5、`m_production_number_variants`導入）
- `documents/die_production_number/2026-09-07_press_directive_step1.md`（Step1、`press_directive.html`新規作成。オーダーシート連携あり版）
- `documents/die_production_number/2026-09-19_t_press_variant_plan.md`（t_pressへのvariant_id追加方針。本ドキュメントの4節・6節4項で一部方針を上書き：ordersheet_idは追加しない）
