-- 2026-11-13 stg_inventory_snapshot: add stock quantity (Excel X) and allocated quantity (Excel Y)
-- Data columns after this: production_number (B), stock_quantity (X), allocated_quantity (Y), unallocated_inventory (Z)
-- Management columns kept: id, source_file_date, created_at
-- Local: run 2026-11-13. Production: run by the user.

ALTER TABLE stg_inventory_snapshot
  ADD COLUMN stock_quantity INT NOT NULL DEFAULT 0 COMMENT 'Stock quantity (Excel column X)' AFTER production_number,
  ADD COLUMN allocated_quantity INT NOT NULL DEFAULT 0 COMMENT 'Allocated quantity (Excel column Y)' AFTER stock_quantity;
