-- 2026-11-11 Staging tables for the Excel import (stock / shipping instruction)
-- Copied from documents/memo.md 2026/11/11. Already created on production by the user (memo: "どちらのテーブルも作成済み").
-- Run with: mysql --default-character-set=utf8mb4 ... < this file

CREATE TABLE stg_inventory_snapshot (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    source_file_date DATE NOT NULL COMMENT '在庫データ日付',

    production_number VARCHAR(100) NOT NULL COMMENT '品番',

    unallocated_inventory INT DEFAULT 0 COMMENT '未引当在庫数',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_source_file_date (source_file_date),
    INDEX idx_production_number (production_number),
    INDEX idx_date_product (source_file_date, production_number)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;

CREATE TABLE stg_shipping_instruction (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    source_file_date DATE NOT NULL COMMENT '取込日',

    production_number VARCHAR(100) NOT NULL COMMENT '品番',

    quantity INT NULL COMMENT '数量',

    order_date DATE NULL COMMENT '受注日付',

    due_date DATE NULL COMMENT '出荷期限',

    customer_name VARCHAR(255) NULL COMMENT '得意先名',

    shipping_instruction_no VARCHAR(50) NULL COMMENT '出荷指示番号',

    transportation_method VARCHAR(20) NULL COMMENT '搬送方法',

    reply_date DATE NULL COMMENT '出荷回答日',

    ab_status VARCHAR(10) NULL COMMENT 'AB',

    custom_status VARCHAR(50) NULL COMMENT 'OK/遅延/期限にならない',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_file_date(source_file_date),
    INDEX idx_prod_no(production_number),
    INDEX idx_due_date(due_date),
    INDEX idx_ship_instruction(shipping_instruction_no),
    INDEX idx_status(custom_status)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4;
