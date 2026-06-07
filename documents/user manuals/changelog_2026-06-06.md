# 変更ログ（2026-06-06）

## 1. 型検進捗管理画面 新規追加

**対象ファイル**
- `die_handover_progress.html`
- `php/die_handover_progress_php/get_list.php`
- `php/die_handover_progress_php/save.php`
- `php/die_handover_progress_php/delete.php`

**内容**
- 型番検索・インラインフィルタ機能付きの型検進捗一覧を新規作成
- 日本語／ベトナム語 切り替え対応
- 進捗データの閲覧・編集・削除（行単位）が可能
- `t_die_handover_progress` テーブルを新規作成・データ投入

**DB 変更（新規テーブル）**
```sql
CREATE TABLE t_die_handover_progress (
  id                                        INT AUTO_INCREMENT PRIMARY KEY,
  die_id                                    INT NOT NULL,
  original_table_no                         INT,
  die_planning_phase_steps                  INT,
  arrival_at                                DATE,
  vn_production_dimensional_inspection_at   DATE,
  vn_qa_dimensional_inspection_at           DATE,
  submit_dimensional_inspection_to_japan_at DATE,
  jp_dimensional_inspection_at              DATE,
  jp_dimensional_inspection_document_number VARCHAR(50),
  anodizing_quality_check_required_flag     TINYINT(1) DEFAULT 0,
  anodizing_quality_check_at                DATE,
  mass_production_trial_at                  DATE,
  die_handover_at                           DATE,
  mass_production_start_at                  DATE,
  production_site_change_notice             VARCHAR(100),
  dimensional_inspection_by                 VARCHAR(50),
  bcp_flag                                  TINYINT(1) DEFAULT 0,
  die_transfer_ready_flag                   TINYINT(1) DEFAULT 0,
  memo                                      VARCHAR(255),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_handover_progress_die FOREIGN KEY (die_id) REFERENCES m_dies(id)
);
```

---

## 2. 金型立上実績レポート 新規追加

**対象ファイル**
- `die_startup_report.html`
- `php/report_php/get_startup_monthly.php`

**内容**
- Chart.js を使った月別グラフ表示
- 日本語／ベトナム語 切り替え対応

---

## 3. 設備稼働実績レポート 新規追加

**対象ファイル**
- `machine_report.html`
- `php/report_php/get_machine_monthly.php`

**内容**
- Chart.js を使った月別グラフ表示
- 日本語／ベトナム語 切り替え対応

---

## 4. t_die_handover_progress.csv データ追加

**内容**
- 型検進捗管理テーブルへの初期データを CSV 形式で追加・投入

---

---

# Nhật ký thay đổi（2026-06-06）

## 1. Thêm mới màn hình quản lý tiến độ kiểm tra khuôn

**File liên quan**
- `die_handover_progress.html`
- `php/die_handover_progress_php/get_list.php`
- `php/die_handover_progress_php/save.php`
- `php/die_handover_progress_php/delete.php`

**Nội dung**
- Tạo mới danh sách tiến độ kiểm tra khuôn với chức năng tìm kiếm theo mã khuôn và bộ lọc nội tuyến
- Hỗ trợ chuyển đổi tiếng Nhật／tiếng Việt
- Có thể xem, chỉnh sửa, xóa（từng dòng）dữ liệu tiến độ
- Tạo mới và nhập dữ liệu cho bảng `t_die_handover_progress`

**Thay đổi DB（bảng mới）**
```sql
CREATE TABLE t_die_handover_progress (
  id                                        INT AUTO_INCREMENT PRIMARY KEY,
  die_id                                    INT NOT NULL,
  original_table_no                         INT,
  die_planning_phase_steps                  INT,
  arrival_at                                DATE,
  vn_production_dimensional_inspection_at   DATE,
  vn_qa_dimensional_inspection_at           DATE,
  submit_dimensional_inspection_to_japan_at DATE,
  jp_dimensional_inspection_at              DATE,
  jp_dimensional_inspection_document_number VARCHAR(50),
  anodizing_quality_check_required_flag     TINYINT(1) DEFAULT 0,
  anodizing_quality_check_at                DATE,
  mass_production_trial_at                  DATE,
  die_handover_at                           DATE,
  mass_production_start_at                  DATE,
  production_site_change_notice             VARCHAR(100),
  dimensional_inspection_by                 VARCHAR(50),
  bcp_flag                                  TINYINT(1) DEFAULT 0,
  die_transfer_ready_flag                   TINYINT(1) DEFAULT 0,
  memo                                      VARCHAR(255),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_handover_progress_die FOREIGN KEY (die_id) REFERENCES m_dies(id)
);
```

---

## 2. Thêm mới báo cáo thực tế lắp đặt khuôn

**File liên quan**
- `die_startup_report.html`
- `php/report_php/get_startup_monthly.php`

**Nội dung**
- Hiển thị biểu đồ theo tháng bằng Chart.js
- Hỗ trợ chuyển đổi tiếng Nhật／tiếng Việt

---

## 3. Thêm mới báo cáo hoạt động thiết bị

**File liên quan**
- `machine_report.html`
- `php/report_php/get_machine_monthly.php`

**Nội dung**
- Hiển thị biểu đồ theo tháng bằng Chart.js
- Hỗ trợ chuyển đổi tiếng Nhật／tiếng Việt

---

## 4. Thêm dữ liệu t_die_handover_progress.csv

**Nội dung**
- Thêm và nhập dữ liệu ban đầu vào bảng quản lý tiến độ kiểm tra khuôn dưới dạng CSV
