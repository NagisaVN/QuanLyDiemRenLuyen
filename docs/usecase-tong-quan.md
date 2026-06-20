# Use Case Tổng Quan

Tài liệu này mô tả các tác nhân và use case chính của hệ thống quản lý điểm rèn luyện. Nội dung được tổng hợp từ `routes/web.php`, `routes/auth.php`, các controller trong `app/Http/Controllers` và service nghiệp vụ trong `app/Services`.

## Tác Nhân

| Tác nhân | Role/code chính | Mục tiêu sử dụng |
| --- | --- | --- |
| Khách | Chưa đăng nhập | Xem trang chào mừng, đăng nhập, yêu cầu đặt lại mật khẩu. |
| Người dùng đã đăng nhập | Mọi role | Vào dashboard theo vai trò, cập nhật hồ sơ, đổi mật khẩu, đăng xuất. |
| Quản trị viên | `admin` | Quản trị dữ liệu nền, người dùng, phân quyền, cấu hình học kỳ, tiêu chí, hoạt động, thông báo, nhật ký, sao lưu và đợt đánh giá. |
| Sinh viên | `sinh_vien` | Tự đánh giá điểm rèn luyện, tải minh chứng, nộp phiếu, xem lịch sử điểm, đăng ký và điểm danh hoạt động. |
| GVCN/Cố vấn | `gvcn` | Xem phiếu của lớp phụ trách, chấm/duyệt điểm cấp GVCN, duyệt minh chứng. |
| Cán bộ Đoàn - Hội | `can_bo_doan_hoi` | Quản lý hoạt động, duyệt đăng ký, điểm danh, phát QR và cộng/trừ điểm hoạt động. |
| Hội đồng khoa/Công tác sinh viên | `hoi_dong_khoa` | Xác nhận điểm cuối cùng, khóa phiếu, xuất báo cáo và quản lý đợt đánh giá nếu có quyền tương ứng. |

## Use Case Chính

### Xác Thực Và Tài Khoản

| Use case | Tác nhân | Kết quả chính |
| --- | --- | --- |
| Đăng nhập | Khách | Đăng nhập thành công và chuyển đến `/dashboard`, sau đó hệ thống redirect theo role. |
| Quên mật khẩu | Khách | Gửi email reset nếu email hợp lệ. |
| Đặt lại mật khẩu | Khách | Cập nhật mật khẩu mới và redirect về `/login`. |
| Xác thực email | Người dùng đã đăng nhập | Xác thực email rồi chuyển về dashboard/intended URL. |
| Xác nhận mật khẩu | Người dùng đã đăng nhập | Xác nhận lại mật khẩu rồi chuyển về dashboard/intended URL. |
| Cập nhật hồ sơ | Người dùng đã đăng nhập | Lưu thông tin hồ sơ, nếu đổi email thì đặt lại trạng thái xác thực email. |
| Đăng xuất | Người dùng đã đăng nhập | Hủy session và redirect về `/`. |

### Quản Lý Đợt Đánh Giá

| Use case | Tác nhân | Kết quả chính |
| --- | --- | --- |
| Xem danh sách đợt đánh giá | Admin, Hội đồng khoa có quyền | Theo dõi các đợt theo năm học/học kỳ/trạng thái. |
| Tạo/sửa đợt đánh giá | Admin, Hội đồng khoa có quyền | Cấu hình thời gian sinh viên tự đánh giá, GVCN duyệt và ngày công bố. |
| Mở đợt đánh giá | Người có quyền `open_dot_danh_gia` | Đợt chuyển sang `open`; phiếu bị khóa tự động nhưng chưa approved có thể được mở lại thành `submitted`. |
| Đóng đợt đánh giá | Người có quyền `close_dot_danh_gia` | Đợt chuyển sang `closed`. |
| Công bố đợt đánh giá | Người có quyền `publish_dot_danh_gia` | Đợt chuyển sang `published`; phiếu chưa approved/locked bị khóa. |

Trạng thái đợt đánh giá:

```text
draft -> open -> closed
             \-> published
```

### Tự Đánh Giá Điểm Rèn Luyện

| Use case | Tác nhân | Kết quả chính |
| --- | --- | --- |
| Mở phiếu đánh giá | Sinh viên | Hệ thống tạo/lấy phiếu theo học kỳ active và đợt đánh giá hiện tại. |
| Lưu điểm tự chấm | Sinh viên | Lưu điểm từng tiêu chí, nhận xét và lịch sử chỉnh sửa. |
| Tải minh chứng | Sinh viên | Upload tối đa 5 file `jpg`, `jpeg`, `png`, `pdf`; mỗi file tối đa 5MB. |
| Nộp phiếu | Sinh viên | Phiếu chuyển từ `draft/submitted` sang `submitted`. |
| In phiếu | Sinh viên | Tải PDF phiếu đánh giá. |
| Xem lịch sử điểm | Sinh viên | Xem các điểm rèn luyện đã ghi nhận theo học kỳ. |

Sinh viên chỉ chỉnh sửa khi đợt đang mở cho sinh viên, phiếu chưa được GVCN/Hội đồng duyệt và chưa bị khóa.

### Duyệt Phiếu Cấp GVCN

| Use case | Tác nhân | Kết quả chính |
| --- | --- | --- |
| Xem danh sách phiếu lớp phụ trách | GVCN | Chỉ thấy phiếu của các lớp được phân công. |
| Xem chi tiết phiếu | GVCN | Xem điểm tự chấm, minh chứng và thông tin sinh viên. |
| Lưu điểm GVCN | GVCN | Lưu điểm/nhận xét cấp GVCN. |
| Xác nhận GVCN | GVCN | Phiếu chuyển sang `reviewed`. |
| Duyệt minh chứng | GVCN | Minh chứng chuyển sang `approved`, `rejected` hoặc `pending`. |

GVCN chỉ duyệt trong khoảng thời gian đợt đánh giá mở cho GVCN.

### Xác Nhận Điểm Cuối Cùng

| Use case | Tác nhân | Kết quả chính |
| --- | --- | --- |
| Xem danh sách phiếu đã qua GVCN | Hội đồng khoa | Xem các phiếu `reviewed`, `approved`, `locked`. |
| Lưu điểm Hội đồng | Hội đồng khoa | Lưu điểm/nhận xét cấp Hội đồng. |
| Xác nhận cuối cùng | Hội đồng khoa | Phiếu chuyển sang `approved`, tạo/cập nhật bản ghi điểm rèn luyện cuối cùng. |
| Khóa phiếu | Hội đồng khoa | Phiếu chuyển sang `locked`. |
| Xuất báo cáo | Hội đồng khoa | Tải báo cáo Excel hoặc PDF. |

Trạng thái phiếu đánh giá:

```text
draft -> submitted -> reviewed -> approved -> locked
```

### Quản Lý Hoạt Động Rèn Luyện

| Use case | Tác nhân | Kết quả chính |
| --- | --- | --- |
| Tạo/sửa/xóa hoạt động | Cán bộ Đoàn - Hội | Quản lý hoạt động, tiêu chí cộng điểm, đối tượng khoa, trạng thái và QR. |
| Đăng ký hoạt động | Sinh viên | Tạo đăng ký trạng thái `pending` nếu hoạt động mở, còn chỗ và áp dụng cho khoa của sinh viên. |
| Duyệt đăng ký | Cán bộ Đoàn - Hội | Đăng ký chuyển sang `approved`, `rejected` hoặc `cancelled`. |
| Điểm danh QR | Sinh viên | Quét link QR hợp lệ, hệ thống check-in và redirect về danh sách hoạt động. |
| Điểm danh thủ công | Cán bộ Đoàn - Hội | Nhập mã sinh viên để ghi nhận tham gia. |
| Điều chỉnh điểm thủ công | Cán bộ Đoàn - Hội | Ghi lịch sử cộng/trừ điểm hoạt động kèm lý do. |

Điểm hoạt động được cộng vào điểm cuối cùng khi Hội đồng xác nhận phiếu.

### Quản Trị Dữ Liệu Nền

Admin có các use case CRUD dùng chung cho 15 module:

```text
users, roles, permissions, khoas, lops, sinh-viens, nam-hocs, hoc-kys,
tieu-chis, muc-tieu-chis, minh-chungs, hoat-dongs, thong-baos, logs, backups
```

Các module dùng chung flow: danh sách -> tạo mới / xem chi tiết / sửa / xóa -> quay về danh sách hoặc trang hiện tại tùy thao tác.
