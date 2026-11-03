-- 2026-11-03 押出ごとの品質メモ（品質評価入力で書き、品質サマリーで見る）。1つの欄を上書きする（書いた人・日時は持たない）
ALTER TABLE t_press
  ADD COLUMN quality_note VARCHAR(1000) NULL
  COMMENT 'Quality evaluation memo per extrusion';
