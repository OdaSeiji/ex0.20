# 変更ログ（2026-08-16）

## 1. press_daily_report.html（プレス日報入力ページ）新規作成

**対象ファイル**
- `press_daily_report.html`
- `index.html`（🏭 押出工程メニューに追加）

**内容**
- diereport（ex0.11）の `DailyReportV30.html` をex0.20向けに刷新・再実装
- 基本情報フォーム：日付・金型番号（前方一致検索付きドロップダウン）・押出指示書・生産指示書（発注書、月絞り込みモーダル）・プレス種別・洗浄済み・設備番号・ビレットサイズ／長さ・計画/実績ビレット数・プレス時間・ラム速度・実測金型温度・担当者・実測長さ・ストレッチ・特記事項・添付ファイル
- コンテナ温度／ラム速度・圧力・温度（No.1〜No.5）測定テーブル
- 🍓ボタンから稼働ログ（PLCサンプリング、`t_plc_web_log`）を参照し、金型ごとの稼働区間からコンテナ温度・プレス時間を自動反映
- 日本語／ベトナム語切り替え対応

---

## 2. サブテーブル（ビレット情報・ラック登録・定尺数量登録）実装

**内容**
- ビレット情報（バンドル・数量・ロット・製造先・検査結果・備考）：行の追加・削除、合計本数表示
- ラック登録（順序・ラック番号・数量、重複ラック番号は登録不可）：行の追加・削除、合計本数表示、縦スクロール対応
- 定尺・数量登録（No.・長さ・数量）：行の追加・削除、数量合計表示、縦スクロール対応
- いずれも「全項目入力後にまとめてDB保存」という設計のため、行追加ボタンは「保存」ではなく「追加」表記に統一（画面上部の本保存ボタンと区別するため）

---

## 3. 作業時間ログ（押出時間・ストレッチ時間・切断時間）追加

**対象ファイル**
- `t_time_press`（DBスキーマ修正：`press_id` を `NOT NULL` 化＋外部キー整理）

**内容**
- 押出時間：停止理由コード（ex0.11の停止理由コード体系を移植、約60種）＋開始・終了時刻を記録
- ストレッチ時間・切断時間：日付（MM-DD表示）＋通し番号範囲（No.1〜No.2）＋開始・終了時刻を記録
- いずれもプレス実績（press_id）に必ず紐づく形とし、ex0.11にあった「金型未選択で日付のみの独立ログ」モードは廃止

---

## 4. 保存機能実装（新規保存）

**対象ファイル**
- `php/press_daily_report/save_press.php`（新規）
- `php/press_daily_report/upload_directive_scan.php`（新規）
- `php/press_daily_report/press_common.php`（新規、save/update共通処理）

**内容**
- 基本情報の必須項目・数値レンジがすべて満たされると「保存」ボタンが自動的に有効化
- 押出指示書スキャン画像のアップロード（拡張子ホワイトリスト、diereportと共有の保存先）
- 本体（t_press）＋ビレット情報・ラック登録・定尺数量・作業時間ログ3種の計7テーブルをトランザクションで一括保存
- 保存成功後はフォームを自動クリアし、次のレコード入力へ

---

## 5. 過去データの読み出し・更新機能

**対象ファイル**
- `php/press_daily_report/get_press_detail.php`（新規）
- `php/press_daily_report/update_press.php`（新規）
- `php/press_daily_report/get_summary.php`（実績一覧表示用、新規）

**内容**
- 実績一覧の行をクリックすると、該当レコードの全項目・全サブテーブルをフォームに読み込み（編集モード）
- 編集モードでは「保存」ボタンの代わりに「更新」ボタンが有効化。画面上部に「編集中：ID=◯◯」バッジと「キャンセル」ボタンを表示
- 更新時はサブテーブルを一旦全削除し、フォームの現在内容で作り直す方式（差分管理を省いてシンプルに）
- 添付ファイルは編集時に選び直さなくても既存ファイルを維持

---

---

# Nhật ký thay đổi（2026-08-16）

## 1. Tạo mới press_daily_report.html（Trang nhập báo cáo ngày ép）

**File liên quan**
- `press_daily_report.html`
- `index.html`（thêm vào menu 🏭 Công đoạn ép）

**Nội dung**
- Đổi mới, viết lại `DailyReportV30.html` của diereport（ex0.11）cho phù hợp với ex0.20
- Form thông tin cơ bản: Ngày・Số khuôn（ô tìm kiếm dạng dropdown）・Phiếu chỉ thị ép・Phiếu chỉ thị sản xuất（đơn đặt hàng, modal lọc theo tháng）・Loại ép・Đã rửa?・Số máy・Cỡ／Chiều dài Billet・SL Billet kế hoạch/thực tế・Thời gian ép・Tốc độ Ram・Nhiệt độ khuôn thực đo・Người phụ trách・Chiều dài thực đo・Tỷ lệ kéo giãn・Ghi chú đặc biệt・File đính kèm
- Bảng đo Nhiệt độ Container／Tốc độ・Áp lực・Nhiệt độ Ram（No.1〜No.5）
- Nút 🍓 tham chiếu nhật ký vận hành（PLC sampling, `t_plc_web_log`）, tự động điền nhiệt độ Container・thời gian ép dựa theo khoảng thời gian vận hành của từng khuôn
- Hỗ trợ chuyển đổi tiếng Nhật／tiếng Việt

---

## 2. Thêm các bảng phụ（Thông tin Billet・Đăng ký Rack・Đăng ký chiều dài/SL）

**Nội dung**
- Thông tin Billet（Bundle・SL・Lot・Nơi SX・Kết quả KT・Ghi chú）: thêm/xóa dòng, hiển thị tổng SL
- Đăng ký Rack（Thứ tự・Số Rack・SL, không cho phép trùng số Rack）: thêm/xóa dòng, hiển thị tổng SL, có thanh cuộn dọc
- Đăng ký chiều dài/SL（No.・Dài・SL）: thêm/xóa dòng, hiển thị tổng SL, có thanh cuộn dọc
- Vì thiết kế là "nhập xong hết mới lưu DB một lần", nên nút thêm dòng đổi thành "Thêm" thay vì "Lưu"（để phân biệt với nút Lưu chính ở trên cùng）

---

## 3. Thêm Nhật ký thời gian làm việc（Thời gian ép・Thời gian kéo giãn・Thời gian cắt）

**File liên quan**
- `t_time_press`（sửa schema DB: đổi `press_id` thành `NOT NULL`＋dọn dẹp khóa ngoại）

**Nội dung**
- Thời gian ép: mã lý do dừng（chuyển từ hệ thống mã của ex0.11, khoảng 60 mã）＋thời gian bắt đầu・kết thúc
- Thời gian kéo giãn・Thời gian cắt: ngày（hiển thị dạng MM-DD）＋khoảng số thứ tự（No.1〜No.2）＋thời gian bắt đầu・kết thúc
- Tất cả đều bắt buộc gắn với kết quả ép（press_id）; bỏ chế độ "ghi log độc lập chỉ theo ngày, không chọn khuôn" từng có ở ex0.11

---

## 4. Chức năng lưu（Lưu mới）

**File liên quan**
- `php/press_daily_report/save_press.php`（mới）
- `php/press_daily_report/upload_directive_scan.php`（mới）
- `php/press_daily_report/press_common.php`（mới, xử lý chung cho save/update）

**Nội dung**
- Nút "Lưu" tự động kích hoạt khi các mục bắt buộc và giá trị số của thông tin cơ bản đều hợp lệ
- Upload ảnh scan phiếu chỉ thị ép（giới hạn đuôi file, lưu chung thư mục với diereport）
- Lưu một lần trong transaction cho tổng cộng 7 bảng: bảng chính（t_press）＋Thông tin Billet・Đăng ký Rack・Đăng ký chiều dài/SL・3 loại nhật ký thời gian
- Sau khi lưu thành công, form tự động xóa trắng để nhập bản ghi tiếp theo

---

## 5. Chức năng đọc lại・cập nhật dữ liệu cũ

**File liên quan**
- `php/press_daily_report/get_press_detail.php`（mới）
- `php/press_daily_report/update_press.php`（mới）
- `php/press_daily_report/get_summary.php`（mới, dùng cho danh sách kết quả）

**Nội dung**
- Khi click vào dòng trong danh sách kết quả, toàn bộ dữ liệu bản ghi và các bảng phụ sẽ được nạp vào form（chế độ chỉnh sửa）
- Ở chế độ chỉnh sửa, nút "Cập nhật" sẽ kích hoạt thay cho nút "Lưu". Phía trên màn hình hiển thị nhãn "Đang sửa: ID=◯◯" và nút "Hủy"
- Khi cập nhật, các bảng phụ sẽ bị xóa hết rồi tạo lại theo nội dung hiện tại của form（đơn giản hóa, không quản lý phần chênh lệch）
- File đính kèm vẫn giữ nguyên file cũ nếu không chọn lại file mới khi chỉnh sửa
