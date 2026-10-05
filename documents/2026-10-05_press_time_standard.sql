-- 2026-10-05 押出時間の標準（号機ごと）
CREATE TABLE m_press_time_standard (
  press_machine     TINYINT  NOT NULL COMMENT '号機（1〜4）',
  startup_sec       INT      NULL     COMMENT '金型交換直後の、自動運転までの時間（秒）',
  billet_change_sec INT      NULL     COMMENT 'ビレットを交換する時間（秒）',
  updated_at        DATETIME NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  updated_staff_id  INT      NULL     COMMENT '更新した人（m_staff.id）',
  PRIMARY KEY (press_machine),
  CONSTRAINT fk_press_time_standard_staff FOREIGN KEY (updated_staff_id) REFERENCES m_staff (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='押出時間の標準（号機ごと）';

-- 号機 1〜4 の行（値は画面で入れる）
INSERT INTO m_press_time_standard (press_machine) VALUES (1), (2), (3), (4);
