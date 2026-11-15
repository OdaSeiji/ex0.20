-- 2026-11-15 stg_shipping_instruction: match the real Excel (Reply term data)
--   I 出荷返答 (PC)  -> reply_date      (was read from J by mistake)
--   J 出荷日付       -> shipping_date   (new; actual shipping date, empty = not shipped yet)
--   S COUNTRY        -> country         (new; was read as custom_status by mistake)
--   R Custom         -> not imported    (custom_status is dropped)
-- Local: run 2026-11-15. Production: run by the user, then re-import the order Excel.

ALTER TABLE stg_shipping_instruction
  DROP INDEX idx_status,
  DROP COLUMN custom_status,
  MODIFY COLUMN reply_date DATE NULL COMMENT 'Shipping reply date (Excel column I)',
  ADD COLUMN shipping_date DATE NULL COMMENT 'Actual shipping date (Excel column J, empty = not shipped yet)' AFTER reply_date,
  ADD COLUMN country VARCHAR(50) NULL COMMENT 'Destination country (Excel column S)' AFTER ab_status,
  ADD INDEX idx_shipping_date (shipping_date);
