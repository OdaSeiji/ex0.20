# 変更ログ（2026-06-15）

## 1\. 部品登録画面 新規追加（die\_part\_import.html）

**対象ファイル**

- `die\_part\_import.html`（新規）
- `php/die\_import/upload\_parts\_csv.php`（新規）
- `php/die\_import/transfer\_to\_parts.php`（新規）

**内容**

- CSVから部品（`is\_accessory\_item\_flag = 1`）を一括登録する画面を新設
- 必要な CSV 列は `die_number` と `is_accessory_item_flag` の **2列のみ**
- `is_accessory_item_flag = 1` の行のみ登録対象（それ以外はスキップ）
- 転送時に `m_dies`・`t_die_handover`（`is_accessory_item_flag = 1`）・`t_die_handover_progress` の3テーブルに同時登録

\---

## 2\. index.html — 「新規、金型、品番登録」アコーディオン追加

**対象ファイル**

- `index.html`

**内容**

- 「📋 新規、金型、品番登録」アコーディオンをメニューに追加
  - 「新規金型登録（die\_import.html）」カード
  - 「部品登録（die\_part\_import.html）」カード
- `die_import.html` カードを「各種設定」から上記アコーディオンへ移動
- `die_import.html` タイトルに「部品登録を除く」を赤字で追記

\---

## 3\. handover\_list.html — 到着日の編集対応・CSVインポートボタン不活性化

**対象ファイル**

- `handover\_list.html`

**内容**

- 編集モードで「到着日」が日付入力フィールドになり、編集・保存が可能に
  （従来は表示のみ）
- 「📂 CSV インポート」ボタンを不活性化（現在は使用しない）

\---

## 4\. その他：内部修正

**対象ファイル**

- `php/die\_arrival/get\_pending\_dies.php`
- `php/handover/insert\_die\_handover.php`

**内容**

- `die_arrival.html` の表示条件に `is_accessory_item_flag = 1` の金型を追加（部品も到着日入力の対象に）
- `t_die_handover` への INSERT 時に `is_accessory_item_flag` を含めるよう修正

\---

# Nhật ký thay đổi（2026-06-15）

## 1\. Thêm mới màn hình đăng ký linh kiện（die\_part\_import.html）

**File liên quan**

- `die\_part\_import.html`（mới）
- `php/die\_import/upload\_parts\_csv.php`（mới）
- `php/die\_import/transfer\_to\_parts.php`（mới）

**Nội dung**

- Thêm mới màn hình đăng ký hàng loạt linh kiện（`is\_accessory\_item\_flag = 1`）từ file CSV
- Cột CSV cần thiết chỉ gồm **2 cột**: `die_number` và `is_accessory_item_flag`
- Chỉ đăng ký các hàng có `is_accessory_item_flag = 1`（các hàng khác bị bỏ qua）
- Khi chuyển dữ liệu, đồng thời đăng ký vào 3 bảng: `m_dies`・`t_die_handover`（`is_accessory_item_flag = 1`）・`t_die_handover_progress`

\---

## 2\. index.html — Thêm accordion「Đăng ký khuôn & linh kiện mới」

**File liên quan**

- `index.html`

**Nội dung**

- Thêm accordion「📋 新規、金型、品番登録」vào menu
  - Card「Đăng ký khuôn mới（die\_import.html）」
  - Card「Đăng ký linh kiện（die\_part\_import.html）」
- Chuyển card `die_import.html` từ「各種設定」sang accordion mới
- Thêm chú thích「部品登録を除く」（không bao gồm linh kiện）vào tiêu đề `die_import.html`

\---

## 3\. handover\_list.html — Cho phép chỉnh sửa ngày đến・Vô hiệu hóa nút nhập CSV

**File liên quan**

- `handover\_list.html`

**Nội dung**

- Ở chế độ chỉnh sửa, cột「Ngày đến」hiển thị ô nhập ngày, có thể chỉnh sửa và lưu
  （trước đây chỉ hiển thị）
- Vô hiệu hóa nút「📂 CSV インポート」（hiện tại không sử dụng）

\---

## 4\. Khác：Sửa đổi nội bộ

**File liên quan**

- `php/die\_arrival/get\_pending\_dies.php`
- `php/handover/insert\_die\_handover.php`

**Nội dung**

- Thêm điều kiện hiển thị `is_accessory_item_flag = 1` vào màn hình `die_arrival.html`（linh kiện cũng là đối tượng nhập ngày đến）
- Sửa để đưa `is_accessory_item_flag` vào câu lệnh INSERT vào `t_die_handover`
