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
| `t_press.inserted_at` の追加（ユーザーが実行） | 実行済み | 実行済み |
| `t_press.entry_source` の追加 | 実行済み | 実行済み |

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

## 追記：実績一覧への表示と、過去分の inserted_at を空にする

押出日報（press_daily_report.html）の下の実績一覧に、**入力元**（新画面／旧画面）と **入力日時**（`inserted_at` を mm-dd hh:mm で表示）の2列を追加した。CSV にも出る。
押出日報の入力漏れのチェックに使う（memo.md 20261001 の要望）。

`inserted_at` は後から追加したため、既存の押出には「列を追加した時刻」が一斉に入っていた（ローカルでは 12,307件が 2026-10-01 09:06:21）。
このままだと過去の押出がすべて同じ入力日時に見えるので、**列を空（NULL）にできるようにして、過去分を空にした**。一覧では「-」と表示される。
初期値（CURRENT_TIMESTAMP）は変えていないので、今後の押出には自動で入力日時が入る。

| SQL | ローカル | 本番 |
|---|---|---|
| `inserted_at` を NULL 可にする | 実行済み | 実行済み |
| 列を追加した時刻のままの行を NULL にする | 実行済み（12,307件） | 実行済み（12,307件。本番も 2026-10-01 09:06:21） |

本番は、列を追加した時刻がローカルと違うので、先に調べてから実行する。

```sql
-- ① 列を追加した時刻を調べる（件数がいちばん多い時刻）
SELECT inserted_at, COUNT(*) AS n FROM t_press GROUP BY inserted_at ORDER BY n DESC LIMIT 3;

-- ② NULL 可にして、①の時刻の行だけを NULL にする（完全一致なので、列を追加したあとの本物の入力日時は消えない）
ALTER TABLE t_press MODIFY inserted_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP;
UPDATE t_press SET inserted_at = NULL WHERE inserted_at = '①の1行目の時刻';

-- ③ 確認
SELECT (inserted_at IS NULL) AS is_null, COUNT(*) FROM t_press GROUP BY is_null;
```

対象ファイル（追記分）：`php/press_daily_report/get_summary.php`、`press_daily_report.html`

## 確認したこと

既存の押出の内容をもとに、ローカルで押出日報の保存 API（save_press.php）からテスト用の押出を1件保存し、
`entry_source = 1`、`inserted_at` に保存日時が入ることを確認。テスト用の押出は削除済み。

## 対象ファイル

- `php/press_daily_report/save_press.php`
