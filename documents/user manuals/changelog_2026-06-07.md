# 変更ログ（2026-06-07）

## 1. 発注・出荷日入力画面 新規追加

**対象ファイル**
- `die_order.html`
- `php/die_order/get_pending.php`
- `php/die_order/save.php`

**DB 変更**
```sql
ALTER TABLE t_die_handover
  ADD COLUMN ordered_at DATE NULL COMMENT '発注日',
  ADD COLUMN shipped_at DATE NULL COMMENT '出荷日';
```

**内容**
- 発注待ち金型を一覧表示（型番・品番・発注日・出荷日）
- 型番検索・一括日付入力対応
- テーブルエリアに最大高さ制限＋スクロールを追加

---

## 2. 金型到着日入力画面 新規追加

**対象ファイル**
- `die_arrival.html`
- `php/die_arrival/get_pending_dies.php`
- `php/die_arrival/save_arrival.php`

**内容**
- 到着予定（未到着）の金型を一覧表示
- 一括日付入力に対応
- 到着日を `m_dies`・`t_die_handover`・`t_die_handover_progress` の3テーブルに同時保存

---

## 3. handover_list.html — 発注日・出荷日列追加・レイアウト変更

**内容**
- 「発注日」「出荷日」列を追加
- 型番列を左固定（sticky）に変更
- 列数増加に伴い横スクロールを有効化

**対象ファイル**
- `handover_list.html`
- `php/handover/get_die_handover_list.php`
- `php/handover/update_die_handover.php`

---

## 4. die_import.html — タイトル変更

**内容**
- 画面タイトルを「金型インポート」→「新規金型登録」に変更

---

## 5. index.html — カード追加・名称変更

**内容**
- 新規カードを追加：「発注・出荷日入力（`die_order.html`）」「到着日入力（`die_arrival.html`）」
- 「金型インポート」→「新規金型登録」に名称変更

---

## 6. ユーザーマニュアル追加

**対象ファイル**
- `documents/user manuals/die_arrival_guide.md`
- `documents/user manuals/die_import_guide.md`

**内容**
- 到着日入力画面・新規金型登録画面の操作ガイドを新規作成

---

---

# Nhật ký thay đổi（2026-06-07）

## 1. Thêm mới màn hình nhập ngày đặt hàng・ngày xuất hàng

**File liên quan**
- `die_order.html`
- `php/die_order/get_pending.php`
- `php/die_order/save.php`

**Thay đổi DB**
```sql
ALTER TABLE t_die_handover
  ADD COLUMN ordered_at DATE NULL COMMENT '発注日',
  ADD COLUMN shipped_at DATE NULL COMMENT '出荷日';
```

**Nội dung**
- Hiển thị danh sách khuôn đang chờ đặt hàng（mã khuôn, số sản phẩm, ngày đặt hàng, ngày xuất hàng）
- Hỗ trợ tìm kiếm theo mã khuôn và nhập ngày hàng loạt
- Thêm giới hạn chiều cao tối đa và cuộn cho khu vực bảng

---

## 2. Thêm mới màn hình nhập ngày đến khuôn

**File liên quan**
- `die_arrival.html`
- `php/die_arrival/get_pending_dies.php`
- `php/die_arrival/save_arrival.php`

**Nội dung**
- Hiển thị danh sách khuôn dự kiến đến（chưa đến）
- Hỗ trợ nhập ngày hàng loạt
- Lưu ngày đến đồng thời vào 3 bảng: `m_dies`・`t_die_handover`・`t_die_handover_progress`

---

## 3. handover_list.html — Thêm cột ngày đặt hàng・ngày xuất hàng・thay đổi bố cục

**Nội dung**
- Thêm cột「Ngày đặt hàng」và「Ngày xuất hàng」
- Cố định cột mã khuôn ở bên trái（sticky）
- Bật cuộn ngang do số cột tăng

**File liên quan**
- `handover_list.html`
- `php/handover/get_die_handover_list.php`
- `php/handover/update_die_handover.php`

---

## 4. die_import.html — Đổi tiêu đề

**Nội dung**
- Đổi tiêu đề màn hình:「金型インポート」→「新規金型登録」（Đăng ký khuôn mới）

---

## 5. index.html — Thêm card・Đổi tên

**Nội dung**
- Thêm card mới:「Nhập ngày đặt hàng・xuất hàng（`die_order.html`）」và「Nhập ngày đến（`die_arrival.html`）」
- Đổi tên「金型インポート」→「新規金型登録」

---

## 6. Thêm hướng dẫn sử dụng

**File liên quan**
- `documents/user manuals/die_arrival_guide.md`
- `documents/user manuals/die_import_guide.md`

**Nội dung**
- Tạo mới hướng dẫn thao tác cho màn hình nhập ngày đến và màn hình đăng ký khuôn mới
