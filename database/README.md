# Hướng dẫn đồng bộ Database cho thành viên nhóm

1. Mở phpMyAdmin: `http://localhost/phpmyadmin`
2. Tạo database mới tên là: `nhom5` (chọn Collation: `utf8mb4_unicode_ci` hoặc `utf8mb4_general_ci`).
3. Chọn database `nhom5` vừa tạo -> Chọn tab **Import (Nhập)** -> Chọn file `database/nhom5.sql` và bấm **Import (Thực hiện)**.
4. Kiểm tra file `wp-config.php` trên máy của bạn:
   - `DB_NAME`: `nhom5`
   - `DB_USER`: `root` (hoặc user MySQL của bạn)
   - `DB_PASSWORD`: mật khẩu MySQL của bạn (nếu XAMPP mặc định thì để trống `''`)
5. Nếu cổng chạy website trên máy của bạn khác `http://localhost:8081/nhom5` (ví dụ `http://localhost/nhom5`):
   - Vào bảng `wp_options` trong phpMyAdmin.
   - Sửa 2 dòng `siteurl` và `home` thành URL thực tế trên máy của bạn.
