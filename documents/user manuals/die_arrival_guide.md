# 金型 到着日入力 使い方ガイド

## 概要

現場に金型が到着した際に、到着日を登録する画面です。  
登録した日付は `m_dies`・`t_die_handover`・`t_die_handover_progress` の3か所に自動で保存されます。

---

## 手順

### ① 画面を開く

トップメニュー →「各種設定」セクション →「金型 到着日入力」をクリック

画面には **到着日が未登録の金型** だけが表示されます。

---

### ② 到着日を入力する

**個別に入力する場合：**

各行の「到着日」列にある日付入力欄をクリックし、日付を選択してください。

| 列 | 内容 |
|---|---|
| 型番 | 金型の型番 |
| 品番 | 紐づく品番 |
| 登録日 | m_dies への登録日 |
| 出荷日 | 日本からの出荷日（参考） |
| 到着日 | ← ここに入力 |

---

**複数の金型に同じ日付を一括入力する場合：**

1. 対象行のチェックボックスにチェックを入れる
2. 画面上部の「到着日：」欄に日付を入力する
3. 「選択行に適用」ボタンをクリック
4. チェックした行すべてに日付が入力されます

---

### ③ 保存する

1. 入力が完了したら画面下部の「**保存**」ボタンをクリック
2. 保存完了後、登録した金型は一覧から自動的に消えます

> ℹ️ 保存ボタンは到着日が1件以上入力されると有効になります。

---

---

# Hướng dẫn sử dụng - Nhập ngày nhập khuôn

## Tổng quan

Màn hình dùng để đăng ký ngày khuôn về đến xưởng.  
Ngày được đăng ký sẽ tự động lưu vào 3 bảng: `m_dies`・`t_die_handover`・`t_die_handover_progress`.

---

## Các bước thực hiện

### ① Mở màn hình

Top Menu →「各種設定」(Cài đặt) →「金型 到着日入力」(Nhập ngày nhập khuôn)

Màn hình chỉ hiển thị các khuôn **chưa có ngày nhập**.

---

### ② Nhập ngày nhập khuôn

**Nhập từng dòng:**

Nhấn vào ô nhập ngày ở cột「到着日」của từng dòng và chọn ngày.

| Cột | Nội dung |
|---|---|
| 型番 | Mã khuôn |
| 品番 | Mã sản phẩm liên kết |
| 登録日 | Ngày đăng ký vào m_dies |
| 出荷日 | Ngày xuất hàng từ Nhật（tham khảo） |
| 到着日 | ← Nhập vào đây |

---

**Nhập cùng một ngày cho nhiều khuôn:**

1. Tích vào ô checkbox của các dòng muốn nhập
2. Nhập ngày vào ô「到着日：」ở đầu màn hình
3. Nhấn nút「選択行に適用」(Áp dụng cho các dòng đã chọn)
4. Ngày sẽ được điền tự động vào tất cả các dòng đã chọn

---

### ③ Lưu dữ liệu

1. Sau khi nhập xong, nhấn nút「**保存**」(Lưu) ở cuối màn hình
2. Sau khi lưu thành công, các khuôn đã đăng ký sẽ tự động biến mất khỏi danh sách

> ℹ️ Nút「保存」sẽ được kích hoạt khi có ít nhất 1 dòng được nhập ngày.
