# 変更ログ（2026-06-04）

## 1. 測定項目 表面粗さ・Diesmark 追加

**DB 変更**
```sql
ALTER TABLE t_die_inspection
  ADD COLUMN surface_roughness VARCHAR(255) NULL AFTER gage,
  ADD COLUMN diesmark          VARCHAR(255) NULL AFTER surface_roughness;
```

**対象ファイル**
- `die_inspection.html`
- `php/die_progress_php/save_inspection.php`
- `php/die_progress_php/get_inspection_data.php`
- `die_progress_list.html`
- `die_diagnosis.html`

**内容**
- 入力欄を CMM・LM/IM・Gage の直後（メモの前）に追加（rows=2）
- 保存・読み込み・プレビュー・診断の読み取り専用表示に反映

---

## 2. handover_list.html — ベトナム語カラム名修正

**内容**

| キー | 旧 | 新 |
|---|---|---|
| col_die | Số khuôn | Mã khuôn |
| col_pn | Số sản phẩm | Mã sản phẩm |
| col_ins_created | Ngày tạo chỉ thị | Ngày tạo điều kiện gia công |
| col_insp_num | Số phiếu kiểm tra | Số kataken |
| col_insp_passed | Ngày qua kiểm tra | Ngày xác nhận kataken |
| col_sub_jp | Ngày gửi Nhật | Ngày gửi mail qua Nhật yêu cầu ikan |
| col_sub_vn | Ngày gửi VN | Ngày đăng ký tài sản VN |
| col_accessory | Phụ kiện | Nửa khuôn |
| col_invoice | Số hóa đơn | Số invoice |
| col_arrived | Ngày đến | Ngày nhập khuôn |
| col_unusable | Không dùng được | Khuôn không dùng được |

---

## 3. index.html — タイトル変更・レイアウト変更

**内容**
- handover_list.html へのメニュータイトルを「金型引継ぎ一覧」→「金型移管書類進捗一覧」に変更
- ダッシュボードの表示順を変更：進捗サマリーを一番上に移動

---

## 4. die_diagnosis.html — 承認後アラート削除

**内容**
- 承認ボタン押下後に表示されていた「承認処理が完了しました」アラートを削除
- 承認後はそのまま画面遷移するよう変更

---

## 5. die_fix_plan.html — 診断情報に型番・押出日を追加

**対象ファイル**
- `die_fix_plan.html`
- `php/die_progress_php/get_fix_plan.php`

**内容**
- 診断情報（読み取り専用）セクションの先頭に「型番」「押出日」を追加
- PHP で `m_dies.die_number` と `t_press.press_date_at` を取得して返すよう修正

---

## 6. die_progress_list.html — 修理計画プレビューに型番・押出日を追加

**対象ファイル**
- `die_progress_list.html`
- `php/die_progress_php/get_fix_plan.php`

**内容**
- 修理計画バッジクリック時のプレビュー画面最上部に「押出金型」「押出日」を追加
- 他のプレビュー（測定・診断・修理報告）では非表示

---

## 7. スタッフ選択のロール絞り込み

**対象ファイル**
- `php/die_progress_php/get_staff_list.php`（`role`/`roles` パラメータ追加）
- `die_inspection.html`
- `die_diagnosis.html`
- `die_fix_plan.html`
- `die_fix_report.html`

**内容**
- `get_staff_list.php` に `?role=xxx`（単一）および `?roles=a,b,null`（複数・NULL含む）フィルタを追加
- 各ページの絞り込み：

| ページ | 対象ロール |
|---|---|
| die_inspection.html（測定者） | `inspector` のみ |
| die_diagnosis.html（診断者） | `die_setup`・`admin`・未設定 |
| die_fix_plan.html（修理計画担当） | `die_setup`・`admin`・未設定 |
| die_fix_report.html（修理報告担当） | `die_setup`・`admin`・未設定 |

---

## 8. die_inspection.html — 編集モードのファイルバリデーション修正

**内容**
- 編集モードで既存添付ファイルが表示されている場合、新規ファイル未選択でも保存できるよう修正
- `preview_area` に `.preview-item` が存在すれば必須チェックをスキップ

---

## 9. die_progress_list.html — 完了/未完了フィルター追加

**内容**
- ステップフィルターボタンに「完了のみ」「未完了のみ」を追加
- 完了の定義：修理不要（ng_action=1 or 4）かつ承認済み、または修理報告済み
- 日本語・ベトナム語対応

---

---

# Nhật ký thay đổi（2026-06-04）

## 1. Thêm mục đo: Độ nhám bề mặt・Diesmark

**Thay đổi DB**
```sql
ALTER TABLE t_die_inspection
  ADD COLUMN surface_roughness VARCHAR(255) NULL AFTER gage,
  ADD COLUMN diesmark          VARCHAR(255) NULL AFTER surface_roughness;
```

**File liên quan**
- `die_inspection.html`
- `php/die_progress_php/save_inspection.php`
- `php/die_progress_php/get_inspection_data.php`
- `die_progress_list.html`
- `die_diagnosis.html`

**Nội dung**
- Thêm ô nhập liệu ngay sau CMM・LM/IM・Gage（trước mục Ghi chú）
- Phản ánh vào lưu, đọc, xem trước và hiển thị chỉ đọc trong chẩn đoán

---

## 2. handover_list.html — Sửa tên cột tiếng Việt

**Nội dung**

| Key | Cũ | Mới |
|---|---|---|
| col_die | Số khuôn | Mã khuôn |
| col_pn | Số sản phẩm | Mã sản phẩm |
| col_ins_created | Ngày tạo chỉ thị | Ngày tạo điều kiện gia công |
| col_insp_num | Số phiếu kiểm tra | Số kataken |
| col_insp_passed | Ngày qua kiểm tra | Ngày xác nhận kataken |
| col_sub_jp | Ngày gửi Nhật | Ngày gửi mail qua Nhật yêu cầu ikan |
| col_sub_vn | Ngày gửi VN | Ngày đăng ký tài sản VN |
| col_accessory | Phụ kiện | Nửa khuôn |
| col_invoice | Số hóa đơn | Số invoice |
| col_arrived | Ngày đến | Ngày nhập khuôn |
| col_unusable | Không dùng được | Khuôn không dùng được |

---

## 3. index.html — Đổi tiêu đề・Thay đổi bố cục

**Nội dung**
- Đổi tiêu đề menu dẫn đến handover_list.html: 「金型引継ぎ一覧」→「金型移管書類進捗一覧」
- Thay đổi thứ tự hiển thị dashboard: chuyển phần tóm tắt tiến độ lên trên cùng

---

## 4. die_diagnosis.html — Xóa thông báo sau khi phê duyệt

**Nội dung**
- Xóa thông báo「承認処理が完了しました」hiển thị sau khi nhấn nút phê duyệt
- Sau khi phê duyệt, chuyển màn hình ngay mà không hiển thị thông báo

---

## 5. die_fix_plan.html — Thêm mã khuôn・ngày đùn vào thông tin chẩn đoán

**File liên quan**
- `die_fix_plan.html`
- `php/die_progress_php/get_fix_plan.php`

**Nội dung**
- Thêm「Mã khuôn」và「Ngày đùn」vào đầu phần thông tin chẩn đoán（chỉ đọc）
- Sửa PHP để lấy `m_dies.die_number` và `t_press.press_date_at`

---

## 6. die_progress_list.html — Thêm mã khuôn・ngày đùn vào xem trước kế hoạch sửa chữa

**File liên quan**
- `die_progress_list.html`
- `php/die_progress_php/get_fix_plan.php`

**Nội dung**
- Thêm「Khuôn đùn」và「Ngày đùn」lên đầu màn hình xem trước khi nhấn badge kế hoạch sửa chữa
- Ẩn đối với các xem trước khác（đo, chẩn đoán, báo cáo sửa chữa）

---

## 7. Lọc role khi chọn nhân viên

**File liên quan**
- `php/die_progress_php/get_staff_list.php`（thêm tham số `role`/`roles`）
- `die_inspection.html`
- `die_diagnosis.html`
- `die_fix_plan.html`
- `die_fix_report.html`

**Nội dung**
- Thêm bộ lọc `?role=xxx`（đơn lẻ）và `?roles=a,b,null`（nhiều giá trị kể cả NULL）vào `get_staff_list.php`
- Giới hạn theo từng trang:

| Trang | Role hiển thị |
|---|---|
| die_inspection.html（người đo） | Chỉ `inspector` |
| die_diagnosis.html（người chẩn đoán） | `die_setup`・`admin`・chưa thiết lập |
| die_fix_plan.html（phụ trách kế hoạch SC） | `die_setup`・`admin`・chưa thiết lập |
| die_fix_report.html（phụ trách báo cáo SC） | `die_setup`・`admin`・chưa thiết lập |

---

## 8. die_inspection.html — Sửa kiểm tra file khi chỉnh sửa

**Nội dung**
- Khi mở ở chế độ chỉnh sửa và đã có file đính kèm hiển thị, cho phép lưu mà không cần chọn file mới
- Bỏ qua kiểm tra bắt buộc nếu có `.preview-item` trong `preview_area`

---

## 9. die_progress_list.html — Thêm bộ lọc hoàn tất/chưa hoàn tất

**Nội dung**
- Thêm nút「Chỉ hoàn tất」và「Chỉ chưa hoàn tất」vào thanh lọc bước
- Định nghĩa hoàn tất: không cần sửa chữa（ng_action=1 hoặc 4）và đã phê duyệt, hoặc đã có báo cáo sửa chữa
- Hỗ trợ tiếng Nhật và tiếng Việt
