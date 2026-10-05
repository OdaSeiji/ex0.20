-- 2026-10-05 金型の作業（t_die_work）と金型の旗（t_die_flag）を新設
-- t_dies_status の代わり。t_dies_status は ex0.11 が使われている間は残す

-- 金型の作業：1行 ＝ 1つの作業（開始〜終了）
CREATE TABLE t_die_work (
  id                INT          NOT NULL AUTO_INCREMENT,
  die_id            INT          NOT NULL COMMENT 'Die (m_dies.id)',
  die_status_id     INT          NOT NULL COMMENT 'Work type (m_die_status.id): washing, grinding, repair, nitriding, measuring, on rack',
  press_id          INT          NULL     COMMENT 'Press that triggered this (t_press.id)',
  started_at        DATETIME     NOT NULL COMMENT 'Start time',
  started_staff_id  INT          NULL     COMMENT 'Started by (m_staff.id)',
  ended_at          DATETIME     NULL     COMMENT 'End time (NULL = in progress)',
  ended_staff_id    INT          NULL     COMMENT 'Ended by (m_staff.id)',
  tank              VARCHAR(11)  NULL     COMMENT 'Washing tank number (washing only)',
  value             VARCHAR(20)  NULL     COMMENT 'Measured value etc. (t_dies_status.specific_value)',
  file_url          VARCHAR(400) NULL     COMMENT 'Photo (t_dies_status.file_url). Add a table if multiple photos are needed',
  note              VARCHAR(400) NULL     COMMENT 'Note',
  entry_source      TINYINT      NOT NULL DEFAULT 1 COMMENT 'Entry source 0=migrated from t_dies_status 1=ex0.20',
  legacy_status_id  INT          NULL     COMMENT 'Original t_dies_status.id',
  created_at        DATETIME     NULL     DEFAULT CURRENT_TIMESTAMP COMMENT 'Created at',
  updated_at        DATETIME     NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated at',
  PRIMARY KEY (id),
  KEY idx_die_work_die (die_id, started_at),
  KEY idx_die_work_open (ended_at),
  KEY idx_die_work_status (die_status_id),
  KEY idx_die_work_press (press_id),
  UNIQUE KEY uq_die_work_legacy (legacy_status_id),
  CONSTRAINT fk_die_work_die     FOREIGN KEY (die_id)           REFERENCES m_dies (id),
  CONSTRAINT fk_die_work_status  FOREIGN KEY (die_status_id)    REFERENCES m_die_status (id),
  CONSTRAINT fk_die_work_press   FOREIGN KEY (press_id)         REFERENCES t_press (id),
  CONSTRAINT fk_die_work_started FOREIGN KEY (started_staff_id) REFERENCES m_staff (id),
  CONSTRAINT fk_die_work_ended   FOREIGN KEY (ended_staff_id)   REFERENCES m_staff (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Die work (start to end). Replaces t_dies_status';

-- 金型の旗：NG（洗浄が必要）などの状態。立てる〜下ろす
CREATE TABLE t_die_flag (
  id                INT          NOT NULL AUTO_INCREMENT,
  die_id            INT          NOT NULL COMMENT 'Die (m_dies.id)',
  flag_status_id    INT          NOT NULL COMMENT 'Flag type (m_die_status.id): NG, NG Rz/Die mark, NG dimension',
  press_id          INT          NULL     COMMENT 'Press that triggered this (t_press.id)',
  set_at            DATETIME     NOT NULL COMMENT 'Flag set time',
  set_staff_id      INT          NULL     COMMENT 'Flag set by (m_staff.id)',
  cleared_at        DATETIME     NULL     COMMENT 'Flag cleared time (NULL = flag is up)',
  cleared_staff_id  INT          NULL     COMMENT 'Flag cleared by (m_staff.id)',
  cleared_work_id   INT          NULL     COMMENT 'Work that cleared the flag (t_die_work.id), e.g. washing',
  note              VARCHAR(400) NULL     COMMENT 'Note',
  entry_source      TINYINT      NOT NULL DEFAULT 1 COMMENT 'Entry source 0=migrated from t_dies_status 1=ex0.20',
  legacy_status_id  INT          NULL     COMMENT 'Original t_dies_status.id',
  created_at        DATETIME     NULL     DEFAULT CURRENT_TIMESTAMP COMMENT 'Created at',
  updated_at        DATETIME     NULL     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated at',
  PRIMARY KEY (id),
  KEY idx_die_flag_die (die_id, set_at),
  KEY idx_die_flag_open (cleared_at),
  KEY idx_die_flag_press (press_id),
  UNIQUE KEY uq_die_flag_legacy (legacy_status_id),
  CONSTRAINT fk_die_flag_die     FOREIGN KEY (die_id)           REFERENCES m_dies (id),
  CONSTRAINT fk_die_flag_status  FOREIGN KEY (flag_status_id)   REFERENCES m_die_status (id),
  CONSTRAINT fk_die_flag_press   FOREIGN KEY (press_id)         REFERENCES t_press (id),
  CONSTRAINT fk_die_flag_set     FOREIGN KEY (set_staff_id)     REFERENCES m_staff (id),
  CONSTRAINT fk_die_flag_cleared FOREIGN KEY (cleared_staff_id) REFERENCES m_staff (id),
  CONSTRAINT fk_die_flag_work    FOREIGN KEY (cleared_work_id)  REFERENCES t_die_work (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Die flag (NG = washing needed etc.). Set to cleared';

-- 確認用
SHOW TABLES LIKE 't_die_%';
SELECT table_name, table_comment FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('t_die_work', 't_die_flag');
