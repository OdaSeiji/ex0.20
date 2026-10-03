# 2026-10-03 材質 A6N01 を 6N01A にまとめる

## 目的

6N01 と 6N01A は同じ材質（ユーザー確認、2026-10-03）。昔、間違って「A6N01」で入力されたものがあるため、6N01A にまとめる。

## 調べたこと

- `m_billet_material`：id 3 = `6N01A`、id 4 = `A6N01`
- id 4 を使っているのは品番 **JA8378**（m_production_numbers.id = 650）の1件だけ。`t_import_billet` は 0 件
- ビレット棚卸し（`t_checkbillet`）にも 6N01 と 6N01A の列が別々にある（`A6N01228600` など）。
  9インチの 6N01 は 2022〜2023 年に多く入っていた。最近は 600 に「2本」が続いているだけ。
  生産計画（PressPlanV12）の在庫は 6N01A の列だけを読んでいるので、6N01 の分は入っていない。
  → 棚卸しを ex0.20 に移植するとき、昔の記録は 6N01 と 6N01A を足して 6N01A として移す（もとの `t_checkbillet` は残す）

## DB変更

| SQL | ローカル | 本番 |
|---|---|---|
| 品番 JA8378 の材質を 6N01A（id 3）に変更 | 実行済み（2026-10-03） | 実行済み（2026-10-03、1件） |

```sql
UPDATE m_production_numbers SET billet_material_id = 3 WHERE billet_material_id = 4;
```

確認用：
```sql
SELECT billet_material_id, COUNT(*) FROM m_production_numbers GROUP BY billet_material_id;   -- 4 が無くなる
```

### ビレット棚卸し（t_checkbillet）の 6N01 の本数を 6N01A に移す

| SQL | ローカル | 本番 |
|---|---|---|
| バックアップの表を作り、6N01 の本数を 6N01A に足して 6N01 を 0 にする | 実行済み（2026-10-03） | 実行済み（2026-10-03） |

対象は 999 件（ローカル）。6N01 の合計は 600 が 18,597本、1200 が 143本。
例：2026-09-19 の9インチ 600 は、6N01 が 2本・6N01A が 92本 → 6N01A が 94本。

```sql
-- もとに戻せるように、先にバックアップを作る
CREATE TABLE t_checkbillet_bak_20261003 AS
  SELECT id, A6N01228600, A6N012281200, A6N01A228600, A6N01A2281200 FROM t_checkbillet;

UPDATE t_checkbillet
SET A6N01A228600  = IFNULL(A6N01A228600, 0)  + IFNULL(A6N01228600, 0),
    A6N01A2281200 = IFNULL(A6N01A2281200, 0) + IFNULL(A6N012281200, 0),
    A6N01228600   = 0,
    A6N012281200  = 0
WHERE IFNULL(A6N01228600, 0) <> 0 OR IFNULL(A6N012281200, 0) <> 0;
```

確認用（上の行の 6N01A と、下の行の合計が同じになり、6N01 は 0 になる）：
```sql
SELECT SUM(IFNULL(A6N01228600,0)) n01_600, SUM(IFNULL(A6N012281200,0)) n01_1200,
       SUM(IFNULL(A6N01A228600,0)) n01a_600, SUM(IFNULL(A6N01A2281200,0)) n01a_1200
FROM t_checkbillet;
SELECT SUM(IFNULL(A6N01228600,0)+IFNULL(A6N01A228600,0)) before_600,
       SUM(IFNULL(A6N012281200,0)+IFNULL(A6N01A2281200,0)) before_1200
FROM t_checkbillet_bak_20261003;
```
ローカルの結果：600 は 60,382本、1200 は 64,625本で一致。
本番の結果：600 は 60,580本、1200 は 64,930本で一致（対象 1,001 件）。

バックアップの表 `t_checkbillet_bak_20261003` は、棚卸しを ex0.20 に移植し終えたら消す。

**注意**：ex0.11 の CheckBilletV4 には 6N01 の入力欄がまだある。現場には 6N01A の欄に入れるよう伝える
（ex0.20 版の棚卸し画面では 6N01A だけにする）。

`m_billet_material` の id 4 の行は **まだ消さない**。ex0.11 の入荷画面（ImportBillet.js）の材質の選択肢に id 4 が直接書いてあり、
消すとそこで A6N01 を選んだときに外部キーのエラーになる。ex0.11 を使わなくなったら消す。

## コードの変更

- `production_number.html`
  - 材質の選択肢から `A6N01`（id 4）を外した
  - CSV 取り込みで「A6N01」と書いてあっても 6N01A（id 3）として登録する（`MATERIAL_MAP`）

※ ex0.21 の production_number.html にも同じ箇所がある（未変更）。移植するときに合わせる。
