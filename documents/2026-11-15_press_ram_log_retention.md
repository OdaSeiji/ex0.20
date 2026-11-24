# 2026-11-15 押出波形（t_press_ram_log）の保存期間を1か月にする — 本番の手順書

## 何をするか

押出の波形（`t_press_ram_log`、0.1秒ごとのラムの位置・速度・圧力・出口温度）は、1か月で約 200MB・約250万件増える
（1号機・3号機の2台分。1年で約 2.4GB）。ほとんど使わないので、**1か月より古い記録は毎日自動で消す**。

- 「1か月」の数え方：実行したときから1か月より前（例：11/15 に動けば 10/15 より前）を消す。
- 消すのは MariaDB の「イベント」（決まった時刻に SQL を自動で実行する仕組み）で行う。
  波形を書き込んでいるのは PLC から集める別のプログラムなので、画面（PHP）ではなく DB 側でやる。
- 消した記録は元に戻せない。押出波形の画面（press_ram_log_chart.html）で見られるのは、直近1か月だけになる。

SQL：`documents/2026-11-15_press_ram_log_retention.sql`（STEP 1〜7。コメントは英語）

## 必要なもの

- 本番PCの MariaDB に **root** でログインできること（イベントを作るには EVENT の権限が要る。webuser には無い）
  ```
  C:\xampp\mysql\bin\mysql.exe -u root -p extrusion
  ```
  phpMyAdmin の「SQL」タブに貼ってもよい。
- 時間の目安：全部で 10〜20分

## 手順

### STEP 1. 確かめる（読むだけ）
SQL の STEP 1 を流して、次をメモする。
- `event_scheduler` が ON か OFF か
- いちばん古い記録（oldest）、件数（total_rows）、1か月より古い件数、テーブルの大きさ（size_mb）

### STEP 2. 日時の索引を足す
毎日の削除で、テーブル全体を見なくて済むようにする。
ローカルでは 258万件で 2.4秒だった。本番は件数が多いので、数十秒かかるかもしれない。
押出中に流しても、波形の書き込みは止まらない。

### STEP 3. イベントの仕組みを ON にする（STEP 1 で OFF だったときだけ）
1. `SET GLOBAL event_scheduler = ON;` を流す。すぐに ON になる（MariaDB の再起動は要らない）。
2. ただし、これだけだと MariaDB を再起動したときに OFF に戻る。
   ずっと ON にするために、`C:\xampp\mysql\bin\my.ini` の `[mysqld]` の下に、次の1行を足して保存する。
   ```
   event_scheduler=ON
   ```
   my.ini は保存するだけでよい（次に MariaDB が起動したときから効く）。今すぐ再起動しなくてよい。

### STEP 4. 最初の片付け（古い記録をまとめて消す）
9月からの数か月分がたまっているので、一度に消すと重い。
SQL の STEP 4 の1行（5万件ずつ消す）を、**「0 rows affected」になるまで何回か繰り返す**。
（例：1か月より古いのが 500万件なら、100回くらい。1回は数秒）

### STEP 5. 毎日のイベントを作る
SQL の STEP 5 を流す。毎日 3:15 に、1か月より古い記録を消す（1日分 約8万件なので軽い）。

### STEP 6. 確かめる
- STEP 6 の SQL で、イベントができていて STATUS が ENABLED か見る。
- **次の日**にもう一度流して、`LAST_EXECUTED` に日時が入っていること、`oldest` が約1か月前になっていることを見る。

### STEP 7.（やらなくてもよい）ファイルを小さくする
行を消しても、DB のファイルの大きさはそのまま（空いたところを次の書き込みで使い回す）。
1か月分で一定になるので、これ以上は大きくならない。今すぐディスクを空けたいときだけ、
押出が止まっている時間に `OPTIMIZE TABLE t_press_ram_log;` を1回流す（数分かかることがある）。

## やめたいとき

```sql
DROP EVENT IF EXISTS ev_press_ram_log_purge;
```
（すでに消した記録は戻らない）

## ローカル（harbor）でやったこと

- 2026-11-15：STEP 2 の索引（`idx_date_time`）を足した（258万件で 2.4秒）。
- 削除とイベントは流していない（ローカルの波形は 9/21〜10/22 の写しなので、流すとほとんど消える。webuser にはイベントの権限も無い）。

## 本番でやったこと（2026-11-17、memo.md 11/17）

- STEP 1：event_scheduler は OFF。483万件（9/21〜）、1か月より古いもの 215万件、405MB。
- STEP 2：索引 `idx_date_time` を足した（13.8秒）。
- STEP 3：`SET GLOBAL event_scheduler = ON` → ON。**my.ini への `event_scheduler=ON` の追加は未確認**。
- STEP 4：1回だけ（5万件）。残りの約 210万件は、11/18 3:15 のイベントでまとめて消える予定。
- STEP 5：イベント `ev_press_ram_log_purge` を作った（ENABLED、STARTS 2026-11-18 03:15）。
- 残り：11/18 に STEP 6 で LAST_EXECUTED と oldest（10/18 ごろ）を確かめる。

## 11/18 本番：STEP 6 で動いたのを確認（memo.md 11/18）

- `ev_press_ram_log_purge`：STATUS `ENABLED`、LAST_EXECUTED **2026-11-18 03:15:00** → 予定どおり動いた。
- `t_press_ram_log`：いちばん古いのは **2026-10-19 06:41:11**（1か月より前は消えた）、残りは 2,709,892行。
- my.ini：`[mysqld]` の中（最後、`[mysqldump]` の直前）に `event_scheduler=ON` があるのを確認（11/18、documents/my.ini）。再起動しても止まらない。**これで完了。**
