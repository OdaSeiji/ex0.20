-- 2026-10-05 押出スケジュール表の、日付・号機ごとの設定（開始の時刻・昼休み）
CREATE TABLE t_press_schedule_day (
  plan_date           DATE     NOT NULL COMMENT 'Press date',
  press_machine       TINYINT  NOT NULL COMMENT 'Press machine (1-4)',
  start_time          TIME     NULL     COMMENT 'Start time (NULL = 08:00)',
  has_lunch           TINYINT  NOT NULL DEFAULT 0 COMMENT 'Lunch break 0=no 1=yes (45 min)',
  lunch_after_plan_id INT      NULL     COMMENT 'Insert lunch after this plan (t_press_plan.id). NULL = die change nearest 12:00',
  updated_at          DATETIME NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated at',
  PRIMARY KEY (plan_date, press_machine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Press schedule settings per date and machine';
