-- 2026-11-13 stg_inventory_snapshot: fix the Excel column names in the comments (comment only, data is not changed)
-- The inventory Excel is: B part number, Y stock, Z allocated, AA unallocated (the earlier SQL said X / Y).
-- Local: run 2026-11-13. Production: run by the user (optional, comment only).

ALTER TABLE stg_inventory_snapshot
  MODIFY COLUMN stock_quantity INT NOT NULL DEFAULT 0 COMMENT 'Stock quantity (Excel column Y)',
  MODIFY COLUMN allocated_quantity INT NOT NULL DEFAULT 0 COMMENT 'Allocated quantity (Excel column Z)';
