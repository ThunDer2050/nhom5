<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!-- Voice Commerce AI Widget -->
<div id="vc-wrapper" class="vc-wrapper vc-pos-<?php echo esc_attr( get_option( 'vc_button_position', 'bottom-right' ) ); ?>">

    <!-- Floating Mic Button -->
    <button id="vc-mic-btn" class="vc-mic-btn" title="Nhấn để nói lệnh (Alt+M)" aria-label="Voice Command">
        <span class="vc-mic-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
            </svg>
        </span>
        <span class="vc-pulse-ring"></span>
        <span class="vc-pulse-ring vc-pulse-ring--2"></span>
    </button>

    <!-- Main Panel -->
    <div id="vc-panel" class="vc-panel">
        <!-- Panel Header -->
        <div class="vc-panel-header">
            <div class="vc-logo-wrap">
                <span class="vc-logo">&#127908; Voice AI Assistant</span>
                <span class="vc-badge-gemini">Gemini AI</span>
            </div>
            <div class="vc-header-actions">
                <button id="vc-tts-toggle" class="vc-icon-btn vc-tts-active" title="Bật/Tắt âm thanh giọng đọc" aria-label="Toggle Speech Audio">
                    <svg class="vc-sound-on" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                    </svg>
                    <svg class="vc-sound-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                        <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27l4.73 4.73H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                    </svg>
                </button>
                <button id="vc-close-btn" class="vc-close-btn" aria-label="Đóng">&times;</button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="vc-tabs">
            <button class="vc-tab-btn vc-tab-btn--active" data-tab="live">
                <span class="vc-tab-icon">&#127908;</span> Trực tiếp
            </button>
            <button class="vc-tab-btn" data-tab="menu">
                <span class="vc-tab-icon">&#128203;</span> Menu thao tác
            </button>
        </div>

        <!-- TAB 1: Live Voice & AI -->
        <div id="vc-tab-live" class="vc-tab-content vc-tab-content--active">
            <div id="vc-status" class="vc-status">Nhấn micro hoặc Alt+M để nói lệnh...</div>
            <div id="vc-transcript" class="vc-transcript" placeholder="Giọng nói của bạn sẽ hiển thị ở đây..."></div>
            <div id="vc-ai-reply" class="vc-ai-reply" style="display:none;"></div>
            <div id="vc-result" class="vc-result"></div>

            <!-- Quick Action Chips -->
            <div class="vc-quick-chips">
                <span class="vc-chip" data-cmd="cua hang">&#128722; Cửa hàng</span>
                <span class="vc-chip" data-cmd="gio hang">&#128717;&#65039; Giỏ hàng</span>
                <span class="vc-chip" data-cmd="che do toi">&#127769; Chế độ tối</span>
                <span class="vc-chip" data-cmd="doc noi dung">&#128266; Đọc trang</span>
            </div>
        </div>

        <!-- TAB 2: Categorized Command Menu -->
        <div id="vc-tab-menu" class="vc-tab-content">
            <div class="vc-menu-scroll">
                
                <!-- Category 1: Mua sắm -->
                <div class="vc-menu-cat">
                    <div class="vc-menu-cat-title">&#128722; Mua sắm & Giỏ hàng</div>
                    <div class="vc-menu-grid">
                        <div class="vc-menu-item" data-action="search_prompt">
                            <div class="vc-menu-item-icon">&#128269;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Tìm kiếm sản phẩm</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Tìm bánh tét", "Tìm rượu mận"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_shop">
                            <div class="vc-menu-item-icon">&#127978;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Vào Cửa hàng</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Mở cửa hàng", "Xem sản phẩm"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="view_cart">
                            <div class="vc-menu-item-icon">&#128717;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Xem Giỏ hàng</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Xem giỏ hàng", "Giỏ hàng"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="checkout">
                            <div class="vc-menu-item-icon">&#128179;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Thanh toán đơn hàng</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Thanh toán", "Đặt hàng"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="clear_cart">
                            <div class="vc-menu-item-icon">&#128465;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Xóa sạch giỏ hàng</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Xóa giỏ hàng", "Làm trống giỏ"</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category 2: Điều hướng -->
                <div class="vc-menu-cat">
                    <div class="vc-menu-cat-title">&#129517; Điều hướng Website</div>
                    <div class="vc-menu-grid">
                        <div class="vc-menu-item" data-action="go_home">
                            <div class="vc-menu-item-icon">&#127968;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Về Trang chủ</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Về trang chủ", "Trang chủ"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_about">
                            <div class="vc-menu-item-icon">&#8505;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Trang Giới thiệu</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Xem giới thiệu", "Về chúng tôi"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_news">
                            <div class="vc-menu-item-icon">&#128240;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Tin tức & Bài viết</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Xem tin tức", "Đọc bài viết"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_contact">
                            <div class="vc-menu-item-icon">&#128222;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Liên hệ & Hỗ trợ</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Trang liên hệ", "Hỗ trợ"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_account">
                            <div class="vc-menu-item-icon">&#128100;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Tài khoản của tôi</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Tài khoản", "Đăng nhập"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="history_back">
                            <div class="vc-menu-item-icon">&#9194;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Quay lại trang trước</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Quay lại", "Trở về"</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category 3: Cuộn & Điều khiển -->
                <div class="vc-menu-cat">
                    <div class="vc-menu-cat-title">&#128220; Cuộn trang & Điều khiển</div>
                    <div class="vc-menu-grid">
                        <div class="vc-menu-item" data-action="scroll_top">
                            <div class="vc-menu-item-icon">&#11014;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Lên đầu trang</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Lên đầu trang", "Về đầu trang"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="scroll_bottom">
                            <div class="vc-menu-item-icon">&#11015;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Xuống cuối trang</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Xuống cuối trang", "Cuối trang"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="scroll_down">
                            <div class="vc-menu-item-icon">&#128317;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Cuộn xuống dưới</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Cuộn xuống", "Kéo xuống"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="scroll_up">
                            <div class="vc-menu-item-icon">&#128316;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Cuộn lên trên</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Cuộn lên", "Kéo lên"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="reload_page">
                            <div class="vc-menu-item-icon">&#128260;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Tải lại trang</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Tải lại trang", "F5", "Làm mới"</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category 4: Giao diện & Trợ năng -->
                <div class="vc-menu-cat">
                    <div class="vc-menu-cat-title">&#127912; Giao diện & Trợ năng</div>
                    <div class="vc-menu-grid">
                        <div class="vc-menu-item" data-action="dark_mode">
                            <div class="vc-menu-item-icon">&#127769;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Bật / Tắt Chế độ tối</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Chế độ tối", "Giao diện tối"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="read_page">
                            <div class="vc-menu-item-icon">&#128266;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Đọc nội dung trang</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Đọc nội dung", "Đọc bài viết"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="stop_reading">
                            <div class="vc-menu-item-icon">&#9209;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Dừng giọng đọc</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Dừng đọc", "Ngừng đọc"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="zoom_in">
                            <div class="vc-menu-item-icon">&#128269;&#10133;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Phóng to chữ</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Phóng to chữ", "Chữ to hơn"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="zoom_out">
                            <div class="vc-menu-item-icon">&#128269;&#10134;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Thu nhỏ chữ</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Thu nhỏ chữ", "Chữ nhỏ hơn"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="zoom_reset">
                            <div class="vc-menu-item-icon">&#128260;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Cỡ chữ chuẩn</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Cỡ chữ chuẩn", "Chữ bình thường"</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Panel Footer Hints -->
        <div class="vc-hints">
            <span>💡 Bấm <strong>Alt+M</strong> để nói • Hoặc click trực tiếp menu</span>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div id="vc-toast" class="vc-toast" role="alert"></div>
</div>
