-- 2026-11-04 金型移管日の同期の確認（読むだけ。何も書き換えない）
--   handover_list の固定資産登録日（t_die_handover.asset_registration_applied_at）と
--   移管進捗の金型移管日（t_die_handover_progress.die_handover_at）を金型ごとに比べる
--   金型本体の行 = is_accessory_item_flag が 0 か NULL の行

-- ① 件数のまとめ
WITH h AS (
  SELECT die_id,
         MAX(CASE WHEN IFNULL(is_accessory_item_flag, 0) = 0 THEN NULLIF(asset_registration_applied_at, '0000-00-00') END) AS main_asset,
         MAX(CASE WHEN is_accessory_item_flag = 1 THEN NULLIF(asset_registration_applied_at, '0000-00-00') END)              AS acc_asset,
         COUNT(CASE WHEN IFNULL(is_accessory_item_flag, 0) = 0 THEN 1 END)                                                  AS main_rows
  FROM t_die_handover
  GROUP BY die_id
)
SELECT
  SUM(h.main_asset IS NOT NULL AND p.die_handover_at IS NOT NULL AND h.main_asset =  p.die_handover_at) AS `一致`,
  SUM(h.main_asset IS NOT NULL AND p.die_handover_at IS NOT NULL AND h.main_asset <> p.die_handover_at) AS `食い違い`,
  SUM(h.main_asset IS NOT NULL AND p.die_handover_at IS NULL)                                          AS `一覧だけに日付`,
  SUM(h.main_asset IS NULL AND p.die_handover_at IS NOT NULL AND h.main_rows = 0)                      AS `進捗だけ_本体の行なし`,
  SUM(h.main_asset IS NULL AND p.die_handover_at IS NOT NULL AND h.main_rows > 0)                      AS `進捗だけ_本体の行あり`,
  SUM(h.main_rows > 1)                                                                                 AS `本体の行が2行以上`
FROM h
LEFT JOIN t_die_handover_progress p ON p.die_id = h.die_id;

-- ② 一致していない金型の一覧（一致しているものは出さない）
WITH h AS (
  SELECT die_id,
         MAX(CASE WHEN IFNULL(is_accessory_item_flag, 0) = 0 THEN NULLIF(asset_registration_applied_at, '0000-00-00') END) AS main_asset,
         MAX(CASE WHEN is_accessory_item_flag = 1 THEN NULLIF(asset_registration_applied_at, '0000-00-00') END)              AS acc_asset,
         COUNT(CASE WHEN IFNULL(is_accessory_item_flag, 0) = 0 THEN 1 END)                                                  AS main_rows
  FROM t_die_handover
  GROUP BY die_id
)
SELECT
  d.die_number,
  h.main_asset      AS asset_main,      -- 一覧：金型本体の行の固定資産登録日
  h.acc_asset       AS asset_accessory, -- 一覧：付属品の行の固定資産登録日（いちばん新しいもの）
  h.main_rows,
  p.die_handover_at AS progress_handover, -- 移管進捗の金型移管日
  CASE
    WHEN h.main_asset IS NOT NULL AND p.die_handover_at IS NOT NULL THEN '食い違い'
    WHEN h.main_asset IS NOT NULL                                    THEN '一覧だけに日付'
    WHEN h.main_rows = 0                                             THEN '進捗だけ_本体の行なし'
    ELSE                                                                  '進捗だけ_本体の行あり'
  END AS kind
FROM h
JOIN m_dies d ON d.id = h.die_id
LEFT JOIN t_die_handover_progress p ON p.die_id = h.die_id
WHERE NOT (h.main_asset <=> p.die_handover_at)
ORDER BY kind, d.die_number;
