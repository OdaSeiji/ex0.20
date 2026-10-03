-- 2026-10-03 本番用：ビレット関係のデータ修正
-- 詳細は 2026-10-03_drop_m_checkbillet.md / 2026-10-03_billet_material_6n01.md

-- ① 実行前の確認 ---------------------------------------------------
SELECT COUNT(*) AS m_checkbillet_rows FROM m_checkbillet;                       -- 0 であること
SELECT id, production_number FROM m_production_numbers WHERE billet_material_id = 4;   -- JA8378 の1件のはず
SELECT COUNT(*) AS target_rows FROM t_checkbillet
 WHERE IFNULL(A6N01228600, 0) <> 0 OR IFNULL(A6N012281200, 0) <> 0;

-- ② 使っていない空の表を削除 ---------------------------------------
DROP TABLE m_checkbillet;

-- ③ 品番の材質 A6N01(id 4) → 6N01A(id 3) ---------------------------
UPDATE m_production_numbers SET billet_material_id = 3 WHERE billet_material_id = 4;

-- ④ 棚卸しの 6N01 の本数を 6N01A に移す（先にバックアップ） -----------
CREATE TABLE t_checkbillet_bak_20261003 AS
  SELECT id, A6N01228600, A6N012281200, A6N01A228600, A6N01A2281200 FROM t_checkbillet;

UPDATE t_checkbillet
SET A6N01A228600  = IFNULL(A6N01A228600, 0)  + IFNULL(A6N01228600, 0),
    A6N01A2281200 = IFNULL(A6N01A2281200, 0) + IFNULL(A6N012281200, 0),
    A6N01228600   = 0,
    A6N012281200  = 0
WHERE IFNULL(A6N01228600, 0) <> 0 OR IFNULL(A6N012281200, 0) <> 0;

-- ⑤ 実行後の確認 ---------------------------------------------------
SHOW TABLES LIKE '%checkbillet%';     -- t_checkbillet と t_checkbillet_bak_20261003 だけ
SELECT billet_material_id, COUNT(*) FROM m_production_numbers GROUP BY billet_material_id;   -- 4 が無い
-- 次の2つの結果で、600 どうし・1200 どうしが同じ数字になり、n01 は 0 になる
SELECT SUM(IFNULL(A6N01228600,0)) AS n01_600, SUM(IFNULL(A6N012281200,0)) AS n01_1200,
       SUM(IFNULL(A6N01A228600,0)) AS n01a_600, SUM(IFNULL(A6N01A2281200,0)) AS n01a_1200
  FROM t_checkbillet;
SELECT SUM(IFNULL(A6N01228600,0)+IFNULL(A6N01A228600,0)) AS before_600,
       SUM(IFNULL(A6N012281200,0)+IFNULL(A6N01A2281200,0)) AS before_1200
  FROM t_checkbillet_bak_20261003;
