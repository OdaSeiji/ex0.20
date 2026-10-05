-- 2026-10-05 Change comments to English (production showed '?' because Japanese was garbled when pasted).
-- Only comments change. Data and column definitions are not touched. ASCII only: safe to paste.


-- m_press_time_standard
ALTER TABLE m_press_time_standard COMMENT = 'Standard press time per machine';
ALTER TABLE m_press_time_standard MODIFY COLUMN `press_machine` tinyint(4) NOT NULL COMMENT 'Press machine (1-4)';
ALTER TABLE m_press_time_standard MODIFY COLUMN `startup_sec` int(11) NULL DEFAULT NULL COMMENT 'Seconds from die change to automatic operation';
ALTER TABLE m_press_time_standard MODIFY COLUMN `billet_change_sec` int(11) NULL DEFAULT NULL COMMENT 'Seconds to change a billet';
ALTER TABLE m_press_time_standard MODIFY COLUMN `updated_at` datetime NULL DEFAULT NULL on update current_timestamp() COMMENT 'Updated at';
ALTER TABLE m_press_time_standard MODIFY COLUMN `updated_staff_id` int(11) NULL DEFAULT NULL COMMENT 'Updated by (m_staff.id)';

-- t_press_schedule_day
ALTER TABLE t_press_schedule_day COMMENT = 'Press schedule settings per date and machine';
ALTER TABLE t_press_schedule_day MODIFY COLUMN `plan_date` date NOT NULL COMMENT 'Press date';
ALTER TABLE t_press_schedule_day MODIFY COLUMN `press_machine` tinyint(4) NOT NULL COMMENT 'Press machine (1-4)';
ALTER TABLE t_press_schedule_day MODIFY COLUMN `start_time` time NULL DEFAULT NULL COMMENT 'Start time (NULL = 08:00)';
ALTER TABLE t_press_schedule_day MODIFY COLUMN `has_lunch` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Lunch break 0=no 1=yes (45 min)';
ALTER TABLE t_press_schedule_day MODIFY COLUMN `lunch_after_plan_id` int(11) NULL DEFAULT NULL COMMENT 'Insert lunch after this plan (t_press_plan.id). NULL = die change nearest 12:00';
ALTER TABLE t_press_schedule_day MODIFY COLUMN `updated_at` datetime NULL DEFAULT NULL on update current_timestamp() COMMENT 'Updated at';

-- t_die_work
ALTER TABLE t_die_work COMMENT = 'Die work (start to end). Replaces t_dies_status';
ALTER TABLE t_die_work MODIFY COLUMN `die_id` int(11) NOT NULL COMMENT 'Die (m_dies.id)';
ALTER TABLE t_die_work MODIFY COLUMN `die_status_id` int(11) NOT NULL COMMENT 'Work type (m_die_status.id): washing, grinding, repair, nitriding, measuring, on rack';
ALTER TABLE t_die_work MODIFY COLUMN `press_id` int(11) NULL DEFAULT NULL COMMENT 'Press that triggered this (t_press.id)';
ALTER TABLE t_die_work MODIFY COLUMN `started_at` datetime NOT NULL COMMENT 'Start time';
ALTER TABLE t_die_work MODIFY COLUMN `started_staff_id` int(11) NULL DEFAULT NULL COMMENT 'Started by (m_staff.id)';
ALTER TABLE t_die_work MODIFY COLUMN `ended_at` datetime NULL DEFAULT NULL COMMENT 'End time (NULL = in progress)';
ALTER TABLE t_die_work MODIFY COLUMN `ended_staff_id` int(11) NULL DEFAULT NULL COMMENT 'Ended by (m_staff.id)';
ALTER TABLE t_die_work MODIFY COLUMN `tank` varchar(11) NULL DEFAULT NULL COMMENT 'Washing tank number (washing only)';
ALTER TABLE t_die_work MODIFY COLUMN `value` varchar(20) NULL DEFAULT NULL COMMENT 'Measured value etc. (t_dies_status.specific_value)';
ALTER TABLE t_die_work MODIFY COLUMN `file_url` varchar(400) NULL DEFAULT NULL COMMENT 'Photo (t_dies_status.file_url). Add a table if multiple photos are needed';
ALTER TABLE t_die_work MODIFY COLUMN `note` varchar(400) NULL DEFAULT NULL COMMENT 'Note';
ALTER TABLE t_die_work MODIFY COLUMN `entry_source` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Entry source 0=migrated from t_dies_status 1=ex0.20';
ALTER TABLE t_die_work MODIFY COLUMN `legacy_status_id` int(11) NULL DEFAULT NULL COMMENT 'Original t_dies_status.id';
ALTER TABLE t_die_work MODIFY COLUMN `created_at` datetime NULL DEFAULT current_timestamp() COMMENT 'Created at';
ALTER TABLE t_die_work MODIFY COLUMN `updated_at` datetime NULL DEFAULT current_timestamp() on update current_timestamp() COMMENT 'Updated at';

-- t_die_flag
ALTER TABLE t_die_flag COMMENT = 'Die flag (NG = washing needed etc.). Set to cleared';
ALTER TABLE t_die_flag MODIFY COLUMN `die_id` int(11) NOT NULL COMMENT 'Die (m_dies.id)';
ALTER TABLE t_die_flag MODIFY COLUMN `flag_status_id` int(11) NOT NULL COMMENT 'Flag type (m_die_status.id): NG, NG Rz/Die mark, NG dimension';
ALTER TABLE t_die_flag MODIFY COLUMN `press_id` int(11) NULL DEFAULT NULL COMMENT 'Press that triggered this (t_press.id)';
ALTER TABLE t_die_flag MODIFY COLUMN `set_at` datetime NOT NULL COMMENT 'Flag set time';
ALTER TABLE t_die_flag MODIFY COLUMN `set_staff_id` int(11) NULL DEFAULT NULL COMMENT 'Flag set by (m_staff.id)';
ALTER TABLE t_die_flag MODIFY COLUMN `cleared_at` datetime NULL DEFAULT NULL COMMENT 'Flag cleared time (NULL = flag is up)';
ALTER TABLE t_die_flag MODIFY COLUMN `cleared_staff_id` int(11) NULL DEFAULT NULL COMMENT 'Flag cleared by (m_staff.id)';
ALTER TABLE t_die_flag MODIFY COLUMN `cleared_work_id` int(11) NULL DEFAULT NULL COMMENT 'Work that cleared the flag (t_die_work.id), e.g. washing';
ALTER TABLE t_die_flag MODIFY COLUMN `note` varchar(400) NULL DEFAULT NULL COMMENT 'Note';
ALTER TABLE t_die_flag MODIFY COLUMN `entry_source` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Entry source 0=migrated from t_dies_status 1=ex0.20';
ALTER TABLE t_die_flag MODIFY COLUMN `legacy_status_id` int(11) NULL DEFAULT NULL COMMENT 'Original t_dies_status.id';
ALTER TABLE t_die_flag MODIFY COLUMN `created_at` datetime NULL DEFAULT current_timestamp() COMMENT 'Created at';
ALTER TABLE t_die_flag MODIFY COLUMN `updated_at` datetime NULL DEFAULT current_timestamp() on update current_timestamp() COMMENT 'Updated at';

-- t_press_plan
ALTER TABLE t_press_plan MODIFY COLUMN `billet_origin` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Billet origin 0=undecided 1=Dubai 2=VN';
ALTER TABLE t_press_plan MODIFY COLUMN `billet_length` int(11) NULL DEFAULT NULL COMMENT 'Billet length (mm). NULL = use press directive';
ALTER TABLE t_press_plan MODIFY COLUMN `entry_source` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Entry source 0=old screen (ex0.11) 1=ex0.20 press plan';
ALTER TABLE t_press_plan MODIFY COLUMN `update_at` datetime NULL DEFAULT current_timestamp() on update current_timestamp() COMMENT 'Updated at (auto on update)';

SELECT table_name, table_comment FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('t_die_work', 't_die_flag', 'm_press_time_standard', 't_press_schedule_day');
SELECT column_name, column_comment FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 't_press_plan' AND column_name IN ('billet_origin', 'billet_length', 'entry_source', 'update_at');
-- check: comments are shown in English
