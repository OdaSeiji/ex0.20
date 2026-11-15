-- 2026-11-15 Backorder follow-up: for orders that can't be shipped from stock, keep the person in charge,
-- the delivery date they promise and a comment, per order (shipping instruction no. = order Excel column G).
-- Staging (stg_shipping_instruction) is replaced on every import, so these are kept in their own tables.
-- Local: run 2026-11-15. Production: run by the user.

CREATE TABLE t_backorder_followup (
  id INT NOT NULL AUTO_INCREMENT,
  shipping_instruction_no VARCHAR(50) NOT NULL COMMENT 'Order key: shipping instruction no. (order Excel column G)',
  production_number VARCHAR(100) NOT NULL COMMENT 'Part number as written in the order Excel (for reference)',
  staff_id INT NULL COMMENT 'Person in charge (m_staff.id)',
  planned_date DATE NULL COMMENT 'Delivery date entered by the person in charge',
  comment VARCHAR(1000) NULL COMMENT 'Comment',
  updated_by INT NULL COMMENT 'Who saved last (m_staff.id)',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_shipping_instruction_no (shipping_instruction_no),
  KEY idx_production_number (production_number),
  CONSTRAINT fk_backorder_staff FOREIGN KEY (staff_id) REFERENCES m_staff (id),
  CONSTRAINT fk_backorder_updated_by FOREIGN KEY (updated_by) REFERENCES m_staff (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Backorder follow-up: person in charge, delivery date and comment per order';

CREATE TABLE t_backorder_followup_log (
  id INT NOT NULL AUTO_INCREMENT,
  followup_id INT NOT NULL COMMENT 't_backorder_followup.id',
  staff_id INT NULL COMMENT 'Person in charge after the change (m_staff.id)',
  planned_date DATE NULL COMMENT 'Delivery date after the change',
  comment VARCHAR(1000) NULL COMMENT 'Comment after the change',
  changed_by INT NULL COMMENT 'Who saved (m_staff.id)',
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_followup (followup_id, changed_at),
  CONSTRAINT fk_backorder_log_followup FOREIGN KEY (followup_id) REFERENCES t_backorder_followup (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='History of t_backorder_followup changes';
