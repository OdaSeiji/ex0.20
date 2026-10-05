-- 2026-10-05 押出計画にビレットの種類と長さを追加
ALTER TABLE t_press_plan
  ADD COLUMN billet_origin TINYINT NOT NULL DEFAULT 0 COMMENT 'ビレットの種類 0=未定 1=Dubai 2=VN' AFTER quantity,
  ADD COLUMN billet_length INT     NULL              COMMENT 'ビレットの長さ(mm)。NULL は押出指示書の長さを使う' AFTER billet_origin;
