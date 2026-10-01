# 2026-10-01 押出実績の入力元（ex0.20 押出日報か旧画面か）を記録

## 目的

`t_press` の押出実績が、ex0.20 の押出日報（press_daily_report.html）で入力されたものか、
旧画面（ex0.11 の日報）で入力されたものかを区別できるようにする。

記録するのは **作った画面だけ**。あとから別の画面で直しても、値は変えない。

## 内容

- `t_press.entry_source`（入力元）を追加
  - `0` ＝ 旧画面（ex0.11）。列の初期値なので、ex0.11 は何も変えなくても 0 が入る（ex0.11 は列名を指定して INSERT している）
  - `1` ＝ ex0.20 押出日報
  - 将来ほかの入力元が増えたら 2、3 … を足す
- ex0.20 押出日報の新規保存（`php/press_daily_report/save_press.php`）で `entry_source = 1` を入れる
- 更新（`update_press.php`）では変えない。ex0.11 で作った押出を ex0.20 で直したときに 1 に変わってしまうため
- 既存の押出（12,310件）はすべて 0。ex0.20 押出日報はまだ現場で使われていないので、過去分はほぼ旧画面の入力

同じ日に、ユーザーが `t_press.inserted_at`（`DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`、INSERT 時の日時が自動で入る）を追加している。
`entry_source` はその後ろに置いた。

## DB変更

| SQL | ローカル | 本番 |
|---|---|---|
| `t_press.inserted_at` の追加（ユーザーが実行） | 実行済み | ユーザーが実行 |
| `t_press.entry_source` の追加 | 実行済み | ユーザーが実行 |

`entry_source` は、コードの反映より先に実行すること（無いと押出日報の新規保存がエラーになる）。

```sql
ALTER TABLE t_press
  ADD COLUMN entry_source TINYINT NOT NULL DEFAULT 0
  COMMENT '入力元 0=旧画面(ex0.11) 1=ex0.20 押出日報'
  AFTER inserted_at;
```

確認用：
```sql
SHOW COLUMNS FROM t_press WHERE Field IN ('inserted_at', 'entry_source');
SELECT entry_source, COUNT(*) FROM t_press GROUP BY entry_source;
```

※ コメントに日本語を含むため、mysql コマンドラインでは UTF-8 の SQL ファイルを
`mysql --default-character-set=utf8mb4 ... < file.sql` で流す（引数に直接書くと文字化けする）。

## 確認したこと

既存の押出の内容をもとに、ローカルで押出日報の保存 API（save_press.php）からテスト用の押出を1件保存し、
`entry_source = 1`、`inserted_at` に保存日時が入ることを確認。テスト用の押出は削除済み。

## 対象ファイル

- `php/press_daily_report/save_press.php`
