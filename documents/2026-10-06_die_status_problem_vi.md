# Vấn đề: không nắm được trạng thái của khuôn

**Ngắn gọn, đây là vấn đề: không biết được “khuôn nào hiện đang ở trạng thái có thể đùn ngay”.**

## Vì sao không biết được

1. **Chỉ ghi lại những việc đã làm**
   Có nhập “đã rửa”, “đã đưa về giá khuôn”, nhưng **không ghi “tiếp theo phải làm gì” và “đã làm xong chưa”**.
2. **Nhập liệu bị bỏ sót**
   Có **312 khuôn** sau khi đùn không có bản ghi nào (trong đó **187 khuôn** đã đùn cách đây hơn 3 tháng). Bản ghi đánh bóng (mài) bị dừng từ tháng 3/2026.
3. **Căn cứ để quyết định (kết quả đánh giá NG) không được ghi lại**
   Năm nay chỉ có **25 bản ghi NG**. Từ dữ liệu không biết được khuôn có cần rửa hay không.

## Hậu quả

- **Không chọn được khuôn khi lập kế hoạch ép đùn**
  Những khuôn trên dữ liệu là “đang ở giá (dùng được)” thực tế có thể chưa rửa, chưa đánh bóng xong. Ngay trước khi đùn, người phải kiểm tra trực tiếp khuôn thật.
- **Không thấy được việc còn tồn đọng**
  Việc rửa, đánh bóng bị dồn lại mà không ai nhận ra, đến lúc cần đùn thì khuôn không dùng được.
- **Phán đoán sai việc thấm nitơ và sửa chữa**
  Số lần rửa và chiều dài đùn không được đếm đúng, nên việc phán đoán có cần thấm nitơ hay không cũng bị sai lệch.

## Tóm lại

> **Trạng thái hiện tại của khuôn trên dữ liệu và trên thực tế không khớp nhau, nên việc lập kế hoạch và chuẩn bị đang phải dựa vào trí nhớ của con người và việc kiểm tra khuôn thật.**

Bảng `t_die_work`・`t_die_flag` lần này và **bảng công việc của khuôn** là để xóa bỏ sự sai lệch đó, giúp chỉ cần nhìn dữ liệu là biết được **“khuôn nào đùn được”** và **“việc nào còn tồn đọng”**.

---

*Số liệu: dữ liệu local (sao chép từ máy chính thức), tính đến ngày 2026-10-01.*
