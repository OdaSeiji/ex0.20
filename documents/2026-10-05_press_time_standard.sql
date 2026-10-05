-- 2026-10-05 押出時間の標準（号機ごと）
CREATE TABLE m_press_time_standard (
  press_machine     TINYINT  NOT NULL COMMENT 'Press machine (1-4)',
  startup_sec       INT      NULL     COMMENT 'Seconds from die change to automatic operation',
  billet_change_sec INT      NULL     COMMENT 'Seconds to change a billet',
  updated_at        DATETIME NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated at',
  updated_staff_id  INT      NULL     COMMENT 'Updated by (m_staff.id)',
  PRIMARY KEY (press_machine),
  CONSTRAINT fk_press_time_standard_staff FOREIGN KEY (updated_staff_id) REFERENCES m_staff (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Standard press time per machine';

-- 号機 1〜4 の行（値は画面で入れる）
INSERT INTO m_press_time_standard (press_machine) VALUES (1), (2), (3), (4);
