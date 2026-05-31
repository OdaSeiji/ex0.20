# 金型移管書類 CSV インポート手順

## 1. CSV ファイルの作成

Google スプレッドシートまたは Excel で、以下の形式でデータを入力します。

**1行目（ヘッダー）は固定**

| die_number | invoice_number |
|---|---|
| MSQ10B2-V01D | CVF467 |
| MSQ20B2-V01D | CVF646 |

**注意事項**

- 列名は `die_number`、`invoice_number` を使用する（変更不可）
- 1ファイルに複数行まとめて記入できる
- **保存形式は UTF-8 CSV で保存すること**

> **Google スプレッドシートでの保存方法**  
> ファイル → ダウンロード → CSV（.csv）

> **Excel での保存方法**  
> 名前を付けて保存 → ファイルの種類 →「CSV UTF-8（コンマ区切り）」を選択

---

## 2. インポート画面を開く

`handover_list.html` を開き、画面左上の **「📂 CSV インポート」** ボタンをクリックします。

---

## 3. CSV ファイルの読み込み

インポートウィンドウが開きます。

**方法 A：ドラッグ＆ドロップ**  
作成した CSV ファイルをドロップゾーンにドラッグします。

**方法 B：クリックして選択**  
ドロップゾーンをクリックし、ファイル選択ダイアログからファイルを選びます。

---

## 4. 型番の候補確認・選択

CSV が読み込まれると、プレビューテーブルが表示されます。

| 表示 | 意味 | 操作 |
|---|---|---|
| ✓（緑） | 型番が1件一致・自動確定 | そのまま進む |
| 要選択（オレンジ） | 候補が複数ある | ドロップダウンから正しい型番を選ぶ |
| ⚠ 未確定（赤） | 一致する型番なし | その行は登録されない |

> 候補が複数ある場合は、必ずドロップダウンで正しい型番を選択してください。

---

## 5. 登録

「**登録 (N件)**」ボタンをクリックします。

- N は確定済みの行数です
- ⚠ 未確定の行は登録されません
- 登録完了後、自動的に一覧が更新されます

---

## 6. 登録内容の確認

一覧画面に戻ったら、**「更新日時」列の見出しをクリックして降順ソート**します。

- 直前に登録したデータが上部に表示されます
- **更新日時セルが黄色** のものは登録・更新から1ヶ月以内のデータです

---

## 補足

- 登録直後は `付属品`・`使用不可` は未設定（空白）の状態です
- 内容を追記・修正する場合は、行のチェックボックスをオンにして **「✏️ チェックのみ編集」** を使用してください

---

---

# Hướng dẫn nhập CSV chứng từ chuyển giao khuôn

## 1. Tạo file CSV

Nhập dữ liệu theo định dạng dưới đây trong Google Spreadsheet hoặc Excel.

**Dòng 1 (tiêu đề) là cố định**

| die_number | invoice_number |
|---|---|
| MSQ10B2-V01D | CVF467 |
| MSQ20B2-V01D | CVF646 |

**Lưu ý**

- Tên cột phải là `die_number` và `invoice_number`（không được thay đổi）
- Có thể nhập nhiều dòng trong một file
- **Lưu file dưới định dạng UTF-8 CSV**

> **Lưu từ Google Spreadsheet**  
> File → Tải xuống → CSV (.csv)

> **Lưu từ Excel**  
> Lưu dưới dạng → Loại file →「CSV UTF-8 (có dấu phẩy)」

---

## 2. Mở màn hình nhập

Mở `handover_list.html`, nhấn nút **「📂 CSV インポート」** ở góc trên bên trái màn hình.

---

## 3. Đọc file CSV

Cửa sổ nhập sẽ mở ra.

**Cách A：Kéo & thả**  
Kéo file CSV vào vùng thả.

**Cách B：Nhấn để chọn**  
Nhấn vào vùng thả, chọn file từ hộp thoại chọn file.

---

## 4. Xác nhận và chọn số khuôn

Sau khi đọc CSV, bảng xem trước sẽ hiển thị.

| Hiển thị | Ý nghĩa | Thao tác |
|---|---|---|
| ✓（xanh lá） | Khớp đúng 1 số khuôn, đã xác nhận tự động | Tiến hành tiếp |
| 要選択 / Cần chọn（cam） | Có nhiều khuôn trùng khớp | Chọn số khuôn đúng từ dropdown |
| ⚠ 未確定 / Chưa xác định（đỏ） | Không tìm thấy số khuôn phù hợp | Dòng này sẽ không được đăng ký |

> Nếu có nhiều khuôn trùng khớp, hãy chắc chắn chọn đúng số khuôn từ dropdown.

---

## 5. Đăng ký

Nhấn nút **「登録 (N件)」**.

- N là số dòng đã được xác nhận
- Các dòng ⚠ chưa xác định sẽ không được đăng ký
- Sau khi đăng ký, danh sách tự động cập nhật

---

## 6. Kiểm tra nội dung đã đăng ký

Sau khi trở về màn hình danh sách, **nhấn tiêu đề cột「更新日時」để sắp xếp giảm dần**.

- Dữ liệu vừa đăng ký sẽ hiển thị ở trên cùng
- **Ô「更新日時」màu vàng** là dữ liệu được đăng ký hoặc cập nhật trong vòng 1 tháng

---

## Ghi chú bổ sung

- Ngay sau khi đăng ký, `付属品`（phụ kiện）và `使用不可`（không sử dụng được）ở trạng thái chưa thiết lập（trống）
- Để bổ sung hoặc chỉnh sửa nội dung, hãy tích vào checkbox của dòng đó và sử dụng **「✏️ チェックのみ編集」**
