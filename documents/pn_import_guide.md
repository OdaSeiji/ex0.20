# 品番 CSV インポート手順

## 1. CSV ファイルの作成

Google スプレッドシートまたは Excel で、以下の形式でデータを入力します。

**1行目（ヘッダー）は固定**

| production_numbers | billet_material | production_length | cross_section_area |
|---|---|---|---|
| ABC-001 | A6063 | 2 | 45.5 |
| ABC-002 | A6061 | 5 | 120.0 |

**注意事項**

- 列名は `production_numbers`（またはスペルは `production_number` でも可）を使用すること
- `production_numbers` 列のみ必須。他の列は空欄でも読み込み可能
- 材質（`billet_material`）に入力できる値：`A6061` / `A6063` / `A6N01A` / `A6N01`（大文字・小文字どちらでも可）
- 材質が空欄または上記以外の値の場合、材質なし（NULL）で登録されます
- **保存形式は UTF-8 CSV で保存すること**

> **Google スプレッドシートでの保存方法**  
> ファイル → ダウンロード → CSV（.csv）

> **Excel での保存方法**  
> 名前を付けて保存 → ファイルの種類 →「CSV UTF-8（コンマ区切り）」を選択

---

## 2. インポート画面を開く

`production_number.html` を開き、画面左上の **「📂 CSV インポート」** ボタンをクリックします。

---

## 3. CSV ファイルの読み込み

インポートウィンドウが開きます。

**方法 A：ドラッグ＆ドロップ**  
作成した CSV ファイルをドロップゾーンにドラッグします。

**方法 B：クリックして選択**  
ドロップゾーンをクリックし、ファイル選択ダイアログからファイルを選びます。

---

## 4. プレビューの確認

CSV が読み込まれると、プレビューテーブルが表示されます。

| 表示 | 意味 | 登録 |
|---|---|---|
| 新規（緑） | DB に存在しない品番 | される |
| 重複（赤） | すでに登録済みの品番 | されない（スキップ） |

画面下部に「新規 N件 / 重複 N件」のサマリーが表示されます。

---

## 5. 登録

「**登録 (N件)**」ボタンをクリックします。

- N は新規の行数です
- 重複の行は登録されません
- 登録完了後、自動的に一覧が更新されます

---

## 6. 登録内容の確認・補完

一覧画面に戻ったら、**「更新日」列の見出しをクリックして降順ソート**します。

- 直前に登録したデータが上部に表示されます
- **更新日セルが黄色** のものは登録・更新から1ヶ月以内のデータです

CSV インポートでは `production_numbers`（品番）以外の項目は省略して登録できます。  
カテゴリ・梱包数・比重など追記が必要な場合は、行の **「✏ 編集」** ボタンから個別に補完してください。

---

---

# Hướng dẫn nhập CSV mã sản phẩm

## 1. Tạo file CSV

Nhập dữ liệu theo định dạng dưới đây trong Google Spreadsheet hoặc Excel.

**Dòng 1 (tiêu đề) là cố định**

| production_numbers | billet_material | production_length | cross_section_area |
|---|---|---|---|
| ABC-001 | A6063 | 2 | 45.5 |
| ABC-002 | A6061 | 5 | 120.0 |

**Lưu ý**

- Tên cột phải là `production_numbers`（hoặc `production_number` cũng được chấp nhận）
- Chỉ cột `production_numbers` là bắt buộc. Các cột còn lại có thể để trống
- Các giá trị hợp lệ cho `billet_material`：`A6061` / `A6063` / `A6N01A` / `A6N01`（chữ hoa hoặc chữ thường đều được）
- Nếu vật liệu để trống hoặc nhập giá trị khác, sẽ được đăng ký với vật liệu NULL
- **Lưu file dưới định dạng UTF-8 CSV**

> **Lưu từ Google Spreadsheet**  
> File → Tải xuống → CSV (.csv)

> **Lưu từ Excel**  
> Lưu dưới dạng → Loại file →「CSV UTF-8 (có dấu phẩy)」

---

## 2. Mở màn hình nhập

Mở `production_number.html`, nhấn nút **「📂 CSV インポート」** ở góc trên bên trái màn hình.

---

## 3. Đọc file CSV

Cửa sổ nhập sẽ mở ra.

**Cách A：Kéo & thả**  
Kéo file CSV vào vùng thả.

**Cách B：Nhấn để chọn**  
Nhấn vào vùng thả, chọn file từ hộp thoại chọn file.

---

## 4. Kiểm tra bản xem trước

Sau khi đọc CSV, bảng xem trước sẽ hiển thị.

| Hiển thị | Ý nghĩa | Đăng ký |
|---|---|---|
| 新規 / Mới（xanh lá） | Mã SP chưa có trong DB | Được đăng ký |
| 重複 / Trùng（đỏ） | Mã SP đã tồn tại | Bỏ qua |

Tóm tắt「Mới N件 / Trùng N件」hiển thị ở cuối bảng.

---

## 5. Đăng ký

Nhấn nút **「登録 (N件)」**.

- N là số dòng mới
- Các dòng trùng sẽ không được đăng ký
- Sau khi đăng ký, danh sách tự động cập nhật

---

## 6. Kiểm tra và bổ sung sau khi đăng ký

Sau khi trở về màn hình danh sách, **nhấn tiêu đề cột「更新日」để sắp xếp giảm dần**.

- Dữ liệu vừa đăng ký sẽ hiển thị ở trên cùng
- **Ô「更新日」màu vàng** là dữ liệu được đăng ký hoặc cập nhật trong vòng 1 tháng

Khi nhập CSV, các mục ngoài `production_numbers` có thể bỏ qua.  
Nếu cần bổ sung danh mục, số lượng đóng gói, trọng lượng riêng v.v., hãy dùng nút **「✏ 編集」** để chỉnh sửa từng dòng.
