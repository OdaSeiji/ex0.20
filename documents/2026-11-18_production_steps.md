# 2026-11-18 本番PCでやること（順番どおりに）

## 11/18 の進み具合

- 手順 1：済み（自動削除は 03:15 に動いた。my.ini の `event_scheduler=ON` も確認）
- 手順 2：済み（`t_press_billet_ram` に 31,090行）
- 手順 3（git pull）：済み（11/24 ユーザー確認：最新まで pull 済み）
- 手順 4（ex0.11 の確認）：不要（11/24 ユーザー：ex0.10/0.11 はもう気にしなくてよい）
- 手順 6（ラムの古い 30列を消す）：**11/25 済み**（memo.md 11/25）。今の版（STEP 0b あり）を流した。
  列 93 → 63、STEP 0b で 2件写した、t_press_billet_ram 31,434行、バックアップ 12,965行。
  （11/24 に「手順 4〜6 済み」と書いたのは間違い。列が 93 あったので、どちらもまだだった）
  → ラムのデータの抜けは無い。`2026-11-24_t_press_ram_after_drop_check.sql` は流さなくてよい（流すなら ③ は 0 のはず）
- **残り：手順 5（使っていない 23列を消す）**。本番では列の数が **63 → 40** になる。

**いちばん大事：手順 2 の SQL を流す前に `git pull` しない。**
pull すると押出日報が新しいテーブル `t_press_billet_ram` を読むようになるので、テーブルが無いと押出日報が保存・表示できなくなる。

結果は今までどおり memo.md に貼って push してください。

---

## 1. 押出波形の自動削除が動いたか（朝、読むだけ）

`documents/2026-11-15_press_ram_log_retention.sql` の **STEP 6 だけ**を流す。

- `LAST_EXECUTED` が `2026-11-18 03:15` ごろになっていれば OK
- `oldest` が 10/18 ごろになっていれば OK
- ついでに my.ini の `[mysqld]` の下に `event_scheduler=ON` があるか見る（無ければ足す）

## 2. ラムのテーブルを作る（**pull の前**）

`documents/2026-11-17_t_press_billet_ram_create.sql` を流す。

- まだ pull していないので、本番の documents にはこのファイルが無い。GitHub の画面で開いて、中身をコピーして流す
- 最後の確認で、No.1〜5 の行数と合計が出る（ローカルは合計 29,650。本番は押出が多いので少し多いはず）
- 古い列はまだ残るので、この時点では何も壊れない

## 3. git pull

- 押出日報を開いて、一覧表が出るか、どれか1件を［編集］で開いてラム速の欄に値が入っているか見る

## 4. ex0.11 が消す列を使っていないか（読むだけ）

QC の確認でまだ ex0.11（V7）を使っているので、消す前に確かめる。フォルダーの場所は本番PCに合わせる。

```
cd C:\xampp\htdocs\ex0.11
findstr /s /i /m "no1_1000 no1_0200 billet_lot_number press_stop_cause_id aging_check_date scrap_weight cutting_ press_date_time_at" *.php *.html *.js
```

- **何も出なければ** 手順 5・6 に進む
- **ファイル名が出たら、5・6 はやらないで** Claude に知らせる

## 5. 使っていない 23列を消す

`documents/2026-11-17_t_press_drop_unused_columns.sql`

- STEP 0 で外部キーの名前が `t_press_m_press_stop_code` 以外だったら、**STEP 2 の前に止めて**知らせる
- 列の数：93 → 70（本番は手順 6 を先に流したので **63 → 40**）

## 6. ラムの古い 30列を消す

`documents/2026-11-17_t_press_billet_ram_drop_old.sql`

- 列の数：70 → 40（本番は 11/25 にこちらを先に流した：93 → 63）
- 終わったら、押出日報をもう一度開いて、一覧表と［編集］が動くか見る

---

## 時間があれば（急がない）

- `documents/2026-11-15_die_handover_rows_check.sql`（読むだけ。金型の到着日の移し方を決めるため）
- 在庫管理 →「品番の対応表」で、候補が出ている 7件を［つなぐ］
- 前からの未確認：`2026-11-06_handover_date_fix.sql`・`2026-11-05_staff_role_packing_qc.sql` は流したかどうか
