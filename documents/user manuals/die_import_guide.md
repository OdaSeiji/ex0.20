# 新規金型登録 使い方ガイド

## 概要

CSVファイルを使って、金型情報をシステムに一括登録する画面です。

---

## 手順

### ① CSVファイルを用意する

以下の列順でCSVファイルを作成してください。**1行目はヘッダー（スキップされます）。**

| A列 | B列 | C列 |
|---|---|---|
| budget_id | die_number | production_number |
| B-001 | ABC-12345 | PN-001 |

- `die_number`（型番）は必須です
- `production_number`（品番）はシステム登録済みのものを入力してください

---

### ② CSVを取り込む

1. 「新規金型登録」画面を開く
2. 「ファイルを選択」でCSVファイルを選ぶ
3. 「取り込む」ボタンをクリック
4. 取り込み結果が画面に表示されます

---

### ③ 一覧を確認・編集する

取り込んだ金型が一覧に表示されます。

| 状態バッジ | 意味 |
|---|---|
| 未転送（青） | まだ登録されていない |
| 転送済（グレー） | 登録完了 |
| エラー（赤） | 問題あり（バッジにカーソルを当てると詳細表示） |

- 品番が自動マッチしない場合は「**編集**」ボタンから手動で設定してください
- 不要な行は「**削除**」ボタンで除外できます

---

### ④ m_dies へ転送する

1. 登録したい行のチェックボックスにチェックを入れる
2. 「選択した行を m_dies へ転送」ボタンをクリック
3. 確認ダイアログで「OK」を選択
4. 転送完了後、状態が「転送済」に変わります

> ⚠️ 品番が未設定の行は転送できません。先に編集してください。

---

---

# Hướng dẫn sử dụng - Đăng ký khuôn mới

## Tổng quan

Màn hình dùng để nhập hàng loạt thông tin khuôn vào hệ thống bằng file CSV.

---

## Các bước thực hiện

### ① Chuẩn bị file CSV

Tạo file CSV theo thứ tự cột dưới đây. **Dòng 1 là tiêu đề (sẽ bị bỏ qua).**

| Cột A | Cột B | Cột C |
|---|---|---|
| budget_id | die_number | production_number |
| B-001 | ABC-12345 | PN-001 |

- `die_number`（mã khuôn）là bắt buộc
- `production_number`（mã sản phẩm）nhập theo dữ liệu đã đăng ký trong hệ thống

---

### ② Nhập file CSV

1. Mở màn hình「新規金型登録」(Đăng ký khuôn mới)
2. Chọn file CSV bằng「ファイルを選択」
3. Nhấn nút「取り込む」(Nhập dữ liệu)
4. Kết quả nhập sẽ hiển thị trên màn hình

---

### ③ Kiểm tra và chỉnh sửa danh sách

Các khuôn đã nhập sẽ hiển thị trong danh sách.

| Trạng thái | Ý nghĩa |
|---|---|
| 未転送・Chưa chuyển（xanh） | Chưa được đăng ký |
| 転送済・Đã chuyển（xám） | Đăng ký hoàn tất |
| エラー・Lỗi（đỏ） | Có vấn đề（rê chuột lên badge để xem chi tiết） |

- Nếu mã sản phẩm không tự động khớp, nhấn「**編集**」(Chỉnh sửa) để thiết lập thủ công
- Nhấn「**削除**」(Xóa) để loại bỏ các dòng không cần thiết

---

### ④ Chuyển dữ liệu vào m_dies

1. Tích vào ô checkbox của các dòng muốn đăng ký
2. Nhấn nút「選択した行を m_dies へ転送」(Chuyển các dòng đã chọn vào m_dies)
3. Chọn「OK」trong hộp thoại xác nhận
4. Sau khi hoàn tất, trạng thái sẽ chuyển sang「転送済」(Đã chuyển)

> ⚠️ Các dòng chưa có mã sản phẩm sẽ không thể chuyển được. Vui lòng chỉnh sửa trước.
