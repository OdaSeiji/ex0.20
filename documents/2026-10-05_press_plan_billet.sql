-- 2026-10-05 押出計画にビレットの種類と長さを追加
ALTER TABLE t_press_plan
  ADD COLUMN billet_origin TINYINT NOT NULL DEFAULT 0 COMMENT 'Billet origin 0=undecided 1=Dubai 2=VN' AFTER quantity,
  ADD COLUMN billet_length INT     NULL              COMMENT 'Billet length (mm). NULL = use press directive' AFTER billet_origin;
