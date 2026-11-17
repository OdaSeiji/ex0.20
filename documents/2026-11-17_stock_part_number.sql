-- 2026-11-17 m_production_numbers: part number as written in the inventory / order Excel
--   Only filled when the Excel part number differs from production_number.
--   An Excel part number is linked to the master when it equals production_number OR stock_part_number.
--   Edited on the page stock_part_map.html (在庫管理 > 品番の対応表).
-- Local: run 2026-11-17. Production: run once (before using the new page).

ALTER TABLE m_production_numbers
  ADD COLUMN stock_part_number VARCHAR(100) NULL
    COMMENT 'Part number in the inventory / order Excel, only when it differs from production_number',
  ADD UNIQUE KEY uk_stock_part_number (stock_part_number);

-- Check
SHOW COLUMNS FROM m_production_numbers LIKE 'stock_part_number';
