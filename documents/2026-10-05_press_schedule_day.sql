-- 2026-10-05 押出スケジュール表の、日付・号機ごとの設定（開始の時刻・昼休み）
CREATE TABLE t_press_schedule_day (
  plan_date           DATE     NOT NULL COMMENT '押出日',
  press_machine       TINYINT  NOT NULL COMMENT '号機（1〜4）',
  start_time          TIME     NULL     COMMENT '開始の時刻（NULL は 8:00）',
  has_lunch           TINYINT  NOT NULL DEFAULT 0 COMMENT '昼休み 0=なし 1=あり（45分）',
  lunch_after_plan_id INT      NULL     COMMENT '昼休みを入れる位置（この計画 t_press_plan.id のあと）。NULL は 12:00 にいちばん近い型の区切り',
  updated_at          DATETIME NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  PRIMARY KEY (plan_date, press_machine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='押出スケジュール表の日付・号機ごとの設定';
