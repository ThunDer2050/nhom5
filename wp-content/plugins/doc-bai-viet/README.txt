=== Đọc Bài Viết ===
Contributors: nhom5
Tags: text-to-speech, tts, vietnamese, doc-bai-viet
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 2.1.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin đọc nội dung bài viết WordPress bằng giọng tiếng Việt — hoàn toàn miễn phí.

== Mô tả ==

Plugin **Đọc Bài Viết** cho phép người dùng nghe nội dung bài viết WordPress
được đọc bằng giọng tiếng Việt.

**Hai chế độ đọc:**

1. **Web Speech API** (ưu tiên): giọng đọc sẵn có trên trình duyệt.
2. **Google Translate TTS** (dự phòng): miễn phí, không cần API Key.

**Hoàn toàn miễn phí — không cần API Key hay tài khoản trả phí.**

**Chức năng:**

* Đọc nội dung bài viết bằng tiếng Việt.
* Tạm dừng, tiếp tục, dừng hoàn toàn.
* Đọc lại từ đầu.
* Điều chỉnh tốc độ đọc.
* Tự động hiển thị ở cuối bài viết.
* Hỗ trợ shortcode [doc_bai_viet].
* Trang thông tin trong Admin (Settings → Đọc Bài Viết).

== Cài đặt ==

1. Tải thư mục `doc-bai-viet` vào `/wp-content/plugins/`.
2. Vào WordPress Admin → Plugins → Kích hoạt "Đọc Bài Viết".
3. Truy cập một bài viết bất kỳ để sử dụng.

Không cần cấu hình thêm. Plugin hoạt động ngay sau khi kích hoạt.

== Sử dụng ==

= Tự động =
Khu vực điều khiển đọc bài viết tự động xuất hiện ở cuối mỗi bài viết.

= Shortcode =
Đặt `[doc_bai_viet]` trong nội dung bài viết để hiển thị ở vị trí tùy ý.

= Các nút điều khiển =
* **Đọc**: Bắt đầu đọc từ đầu.
* **Tạm dừng**: Tạm dừng.
* **Tiếp tục**: Tiếp tục sau khi tạm dừng.
* **Dừng**: Dừng hoàn toàn.

== Cấu trúc thư mục ==

    doc-bai-viet/
    ├── doc-bai-viet.php
    ├── assets/
    │   ├── css/
    │   │   ├── style.css
    │   │   └── admin-style.css
    │   └── js/
    │       └── script.js
    └── README.txt

== Changelog ==

= 2.1.0 =
* Chuyển sang Google Translate TTS (miễn phí hoàn toàn).
* Không cần API Key.
* Trang cài đặt hiển thị thông tin và hướng dẫn.

= 2.0.0 =
* Thêm hỗ trợ Google Cloud TTS API.
* Thêm trang cài đặt Admin.

= 1.0.0 =
* Phiên bản đầu tiên.
