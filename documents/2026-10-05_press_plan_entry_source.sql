-- 2026-10-05 押出計画の入力元と更新日時
ALTER TABLE t_press_plan
  ADD COLUMN entry_source TINYINT NOT NULL DEFAULT 0 COMMENT '入力元 0=旧画面(ex0.11) 1=ex0.20 押出計画' AFTER created_at,
  MODIFY COLUMN update_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時（修正すると自動で更新）';

-- 確認用
SHOW COLUMNS FROM t_press_plan WHERE Field IN ('created_at', 'entry_source', 'update_at');
SELECT entry_source, COUNT(*) FROM t_press_plan GROUP BY entry_source;
