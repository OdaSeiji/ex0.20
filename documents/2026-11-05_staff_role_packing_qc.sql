-- 2026-11-05 m_staff のロールに「梱包」(packing) と「品管」(quality_control) を追加する（memo 2026/10/28）
--   既存の値はそのまま。並びは既存の後ろに足すだけなので、今入っている値は変わらない
ALTER TABLE m_staff
  MODIFY COLUMN role ENUM('operator','maintenance','inspector','die_setup','admin','die_engineer','packing','quality_control') NULL DEFAULT NULL
  COMMENT 'Staff role (packing = packing staff, quality_control = QC staff)';
