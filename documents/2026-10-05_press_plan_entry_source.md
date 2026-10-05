# 2026-10-05 押出計画の入力元（ex0.20 か旧画面か）と更新日時

## 目的

押出実績（t_press.entry_source）・押出指示書（t_press_directive.entry_source）と同じく、
押出計画（t_press_plan）が ex0.20 の押出計画（press_plan.html）で作られたものか、旧画面（ex0.11 PressPlanV12）で作られたものかを区別できるようにする。
あわせて、修正した日時が分かるようにする。

## 調べたこと

- `created_at`（作成日時）は、もともと INSERT のときに自動で入る（全 12,889 件に値あり、2022-05 から）
- `update_at` は列があるが、初期値が作成日時になるだけで、修正しても変わらなかった（ON UPDATE が無い）

## 内容

- `t_press_plan.entry_source`（入力元）を追加
  - `0` ＝ 旧画面（ex0.11）。列の初期値なので、ex0.11 は何も変えなくても 0 が入る（ex0.11 の InsPressPlanV9.php は列名を指定して INSERT）
  - `1` ＝ ex0.20 押出計画。新規保存（`php/press_plan/save_plans.php`）で 1 を入れる
  - 修正（`update_plan.php`）では変えない
  - 既存の計画はすべて 0
- `update_at` を、修正すると自動で更新されるようにした（ON UPDATE CURRENT_TIMESTAMP）
- 押出計画の右の一覧に「入力元」の列（新＝緑、旧＝灰色のバッジ）。マウスを乗せると作成日時と更新日時が出る

## DB変更

| SQL | ローカル | 本番 |
|---|---|---|
| `entry_source` の追加と `update_at` の自動更新 | 実行済み（2026-10-05） | 実行済み（2026-10-05、既存 12,927 件はすべて 0） |

SQL は `2026-10-05_press_plan_entry_source.sql`（日本語のコメントを含むため UTF-8。phpMyAdmin ならそのまま貼り付け）。

```sql
ALTER TABLE t_press_plan
  ADD COLUMN entry_source TINYINT NOT NULL DEFAULT 0 COMMENT '入力元 0=旧画面(ex0.11) 1=ex0.20 押出計画' AFTER created_at,
  MODIFY COLUMN update_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時（修正すると自動で更新）';
```

確認用：
```sql
SHOW COLUMNS FROM t_press_plan WHERE Field IN ('created_at', 'entry_source', 'update_at');   -- update_at の Extra が on update current_timestamp()
SELECT entry_source, COUNT(*) FROM t_press_plan GROUP BY entry_source;                         -- 0 だけ
```

**コードの反映より先に実行すること**（無いと押出計画の新規保存がエラーになる）。

## 確かめたこと

テスト用の計画（2099-01-01）を押出計画で保存 → entry_source = 1、作成 15:00。1分後に本数を修正 → 更新日時が 15:01 に変わり、作成日時と入力元はそのまま。
一覧に「新」のバッジと「作成 … / 更新 …」が出ることを確認。テスト用の計画は削除済み。

## 対象ファイル

- `php/press_plan/save_plans.php`、`php/press_plan/get_plans.php`、`press_plan.html`
