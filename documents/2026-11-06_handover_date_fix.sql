-- 2026-11-06 金型移管日・固定資産登録日の暫定の直し（11/05 の調査 documents/2026-11-04_handover_date_check.sql の続き）
--   決まり：移管と固定資産登録は、金型本体と部品で同じ日。移管日（t_die_handover_progress.die_handover_at）＝ 本体の行の固定資産登録日（t_die_handover.asset_registration_applied_at）
--   A. 部品の行しかない金型（本番 69 型）：部品の印が誤って付いた本体の行を、本体の行に戻す
--   B. 固定資産登録日が空なのに移管日がある金型（本番 12 型）：本体の行の固定資産登録日に、移管日を入れる
--   本番で実行する。上から順に。0〜1 は読むだけ、2〜4 で書き換える

-- 0. 念のため、書き換える前の2つのテーブルを丸ごと写しておく（直したあと問題がなければ、しばらくして DROP してよい）
CREATE TABLE t_die_handover_bak_20261106          AS SELECT * FROM t_die_handover;
CREATE TABLE t_die_handover_progress_bak_20261106 AS SELECT * FROM t_die_handover_progress;

-- 1-A. 直す行の確認：本体の行が無い金型の、備考（note2）が空の部品の行（その金型に1行だけのもの）
SELECT d.die_number, h.id, h.note2, h.die_model_code, h.asset_registration_applied_at, p.die_handover_at
FROM t_die_handover h
JOIN m_dies d ON d.id = h.die_id
LEFT JOIN t_die_handover_progress p ON p.die_id = h.die_id
WHERE h.is_accessory_item_flag = 1
  AND IFNULL(TRIM(h.note2), '') = ''
  AND h.die_id IN (
      SELECT die_id FROM t_die_handover
      GROUP BY die_id
      HAVING SUM(IFNULL(is_accessory_item_flag, 0) = 0) = 0                                   -- 本体の行が無い
         AND SUM(is_accessory_item_flag = 1 AND IFNULL(TRIM(note2), '') = '') = 1             -- 備考が空の部品の行が1行だけ
  )
ORDER BY d.die_number;

-- 1-B. 直す行の確認：本体の行の固定資産登録日が空で、移管日がある金型（A を直したあとの状態で数える）
--      2020-01-01 の2型（MSQ50B2-V03D・MXQ12B-V02D）は仮の日付の疑いがあるので、B では入れない（下の WHERE で除く）
SELECT d.die_number, h.id, h.asset_registration_applied_at, p.die_handover_at
FROM t_die_handover h
JOIN m_dies d ON d.id = h.die_id
JOIN t_die_handover_progress p ON p.die_id = h.die_id
WHERE IFNULL(h.is_accessory_item_flag, 0) = 0
  AND NULLIF(h.asset_registration_applied_at, '0000-00-00') IS NULL
  AND p.die_handover_at IS NOT NULL
ORDER BY d.die_number;

-- 2. ここから書き換え。途中で止めたら ROLLBACK
START TRANSACTION;

-- 3-A. 部品の印を外して本体の行に戻す
UPDATE t_die_handover h
JOIN (
    SELECT die_id FROM t_die_handover
    GROUP BY die_id
    HAVING SUM(IFNULL(is_accessory_item_flag, 0) = 0) = 0
       AND SUM(is_accessory_item_flag = 1 AND IFNULL(TRIM(note2), '') = '') = 1
) x ON x.die_id = h.die_id
SET h.is_accessory_item_flag = 0
WHERE h.is_accessory_item_flag = 1
  AND IFNULL(TRIM(h.note2), '') = '';

-- 3-B. 本体の行の固定資産登録日に、移管日を入れる（2020-01-01 は除く）
UPDATE t_die_handover h
JOIN t_die_handover_progress p ON p.die_id = h.die_id
SET h.asset_registration_applied_at = p.die_handover_at
WHERE IFNULL(h.is_accessory_item_flag, 0) = 0
  AND NULLIF(h.asset_registration_applied_at, '0000-00-00') IS NULL
  AND p.die_handover_at IS NOT NULL
  AND p.die_handover_at <> '2020-01-01';

-- 4. 結果の確認：documents/2026-11-04_handover_date_check.sql の ① をもう一度実行する
--    進捗だけ_本体の行なし ＝ 部品の行しかない金型のうち、備考付きの行しかないもの（自動では直さない）
--    進捗だけ_本体の行あり ＝ 2020-01-01 の2型だけになるはず
--    よければ COMMIT、おかしければ ROLLBACK
-- COMMIT;
-- ROLLBACK;
