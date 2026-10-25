-- 2026-10-25 持ち時間（押出スケジュールの日付・号機ごとの設定に終了の時刻を追加）
ALTER TABLE t_press_schedule_day
  ADD COLUMN end_time TIME NULL DEFAULT NULL COMMENT 'End time of the working day (available time = end - start - lunch)' AFTER start_time;
