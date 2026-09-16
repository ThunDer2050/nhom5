<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!-- Voice Commerce AI & Accessibility Widget (WCAG 2.1) -->
<div id="vc-wrapper" class="vc-wrapper vc-pos-<?php echo esc_attr( get_option( 'vc_button_position', 'bottom-right' ) ); ?>">

    <!-- Floating Buttons Group -->
    <div class="vc-floating-group">
        <!-- Floating Accessibility Button -->
        <button id="vc-a11y-btn" class="vc-floating-sub-btn" title="Chế độ tiếp cận người khiếm thị (Alt+A)" aria-label="Chế độ tiếp cận người khiếm thị">
            <span class="vc-a11y-icon">&#9855;</span>
            <span class="vc-a11y-badge">WCAG</span>
        </button>

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
    </div>

    <!-- Main Panel -->
    <div id="vc-panel" class="vc-panel" role="dialog" aria-modal="true" aria-label="Voice AI and Accessibility Panel">
        <!-- Panel Header -->
        <div class="vc-panel-header">
            <div class="vc-logo-wrap">
                <span class="vc-logo">&#127908; Voice & Accessibility AI</span>
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
            <button class="vc-tab-btn" data-tab="a11y">
                <span class="vc-tab-icon">&#9855;</span> Trợ năng (WCAG)
            </button>
            <button class="vc-tab-btn" data-tab="menu">
                <span class="vc-tab-icon">&#128203;</span> Menu lệnh
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
                <span class="vc-chip" data-cmd="che do khiem thi">&#9855; Chế độ khiếm thị</span>
                <span class="vc-chip" data-cmd="cua hang">&#128722; Cửa hàng</span>
                <span class="vc-chip" data-cmd="kiem tra gio hang">&#128717;&#65039; Đọc giỏ hàng</span>
                <span class="vc-chip" data-cmd="doc san pham">&#128266; Đọc sản phẩm</span>
                <span class="vc-chip" data-cmd="doc noi dung">&#128196; Đọc bài viết</span>
                <span class="vc-chip" data-cmd="che do toi">&#127769; Chế độ tối</span>
            </div>
        </div>

        <!-- TAB 2: Accessibility (WCAG 2.1) Mode -->
        <div id="vc-tab-a11y" class="vc-tab-content">
            <div class="vc-a11y-panel">
                <div class="vc-a11y-banner">
                    <div class="vc-a11y-banner-title">
                        <span>&#9855; Chế độ Hỗ trợ Khiếm thị & Khuyết tật</span>
                        <span id="vc-a11y-status-tag" class="vc-a11y-tag-off">Đang Tắt</span>
                    </div>
                    <p class="vc-a11y-desc">Tiêu chuẩn WCAG 2.1: Tự động đọc to tên nút, sản phẩm khi di chuột / phím Tab, tương phản cao AAA và điều khiển bằng giọng nói.</p>
                </div>

                <!-- Master Toggle -->
                <div class="vc-a11y-card">
                    <button id="vc-a11y-master-toggle" class="vc-a11y-big-btn">
                        <span class="vc-a11y-big-icon">&#9855;</span>
                        <span class="vc-a11y-big-text">
                            <strong>Bật / Tắt Chế độ Khiếm thị</strong>
                            <small>Phím tắt nhanh: <code>Alt + A</code></small>
                        </span>
                    </button>
                </div>

                <!-- Action Grid -->
                <div class="vc-a11y-grid">
                    <button class="vc-a11y-action-btn" data-action="read_page">
                        <span class="vc-a11y-act-icon">&#128266;</span>
                        <span>Đọc toàn bộ trang (Alt+R)</span>
                    </button>
                    <button class="vc-a11y-action-btn" data-action="cart_summary">
                        <span class="vc-a11y-act-icon">&#128722;</span>
                        <span>Đọc tóm tắt giỏ hàng (Alt+C)</span>
                    </button>
                    <button class="vc-a11y-action-btn" data-action="read_products">
                        <span class="vc-a11y-act-icon">&#127822;</span>
                        <span>Đọc danh sách sản phẩm</span>
                    </button>
                    <button class="vc-a11y-action-btn" data-action="high_contrast">
                        <span class="vc-a11y-act-icon">&#128065;&#65039;</span>
                        <span>Tương phản cao WCAG (Vàng Đen)</span>
                    </button>
                    <button class="vc-a11y-action-btn" data-action="zoom_in">
                        <span class="vc-a11y-act-icon">&#10133;</span>
                        <span>Phóng to chữ (+10%)</span>
                    </button>
                    <button class="vc-a11y-action-btn" data-action="stop_reading">
                        <span class="vc-a11y-act-icon">&#9209;&#65039;</span>
                        <span>Dừng đọc ngay (Esc)</span>
                    </button>
                </div>

                <!-- Keyboard shortcuts guide -->
                <div class="vc-a11y-shortcuts">
                    <div class="vc-shortcuts-title">⌨️ Phím tắt tiếp cận nhanh:</div>
                    <div class="vc-shortcut-row"><kbd>Alt + A</kbd><span>Bật/Tắt chế độ khiếm thị</span></div>
                    <div class="vc-shortcut-row"><kbd>Alt + M</kbd><span>Bật micro ra lệnh bằng giọng nói</span></div>
                    <div class="vc-shortcut-row"><kbd>Alt + R</kbd><span>Đọc to nội dung bài viết/trang</span></div>
                    <div class="vc-shortcut-row"><kbd>Alt + C</kbd><span>Đọc tình trạng giỏ hàng & tổng tiền</span></div>
                    <div class="vc-shortcut-row"><kbd>Phím Tab</kbd><span>Di chuyển và nghe đọc từng nút/sản phẩm</span></div>
                    <div class="vc-shortcut-row"><kbd>Phím Esc</kbd><span>Ngắt giọng đọc ngay lập tức</span></div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Menu thao tác đầy đủ -->
        <div id="vc-tab-menu" class="vc-tab-content">
            <div class="vc-menu-scroll">

                <!-- Nhóm 0: Hỗ trợ khiếm thị & Trợ năng -->
                <div class="vc-menu-group">
                    <div class="vc-group-title">&#9855; Hỗ trợ Người Khiếm Thị (WCAG 2.1)</div>
                    <div class="vc-menu-grid">
                        <div class="vc-menu-item" data-action="accessibility_mode">
                            <div class="vc-menu-item-icon">&#9855;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Chế độ khiếm thị</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Chế độ khiếm thị", "Bật trợ năng" (Alt+A)</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="cart_summary">
                            <div class="vc-menu-item-icon">&#128717;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Đọc giỏ hàng</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Kiểm tra giỏ hàng", "Đọc giỏ hàng" (Alt+C)</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="read_products">
                            <div class="vc-menu-item-icon">&#128266;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Đọc danh sách sản phẩm</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Đọc sản phẩm", "Có những món gì"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="high_contrast">
                            <div class="vc-menu-item-icon">&#128065;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Tương phản cao</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Tương phản cao", "Màu tương phản"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="read_page">
                            <div class="vc-menu-item-icon">&#128196;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Đọc nội dung trang</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Đọc trang", "Đọc bài viết" (Alt+R)</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="stop_reading">
                            <div class="vc-menu-item-icon">&#9209;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Dừng giọng đọc</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Dừng đọc", "Ngừng đọc" (Esc)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nhóm 1: Mua sắm & Giỏ hàng -->
                <div class="vc-menu-group">
                    <div class="vc-group-title">&#128722; Mua sắm & Giỏ hàng</div>
                    <div class="vc-menu-grid">
                        <div class="vc-menu-item" data-action="search_prompt">
                            <div class="vc-menu-item-icon">&#128269;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Tìm kiếm đặc sản</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Tìm bánh tét", "Tìm rượu mận"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_shop">
                            <div class="vc-menu-item-icon">&#127978;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Xem Cửa hàng</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Cửa hàng", "Xem sản phẩm"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="view_cart">
                            <div class="vc-menu-item-icon">&#128717;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Mở giỏ hàng</div>
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

                <!-- Nhóm 2: Chuyển trang -->
                <div class="vc-menu-group">
                    <div class="vc-group-title">&#129517; Chuyển trang</div>
                    <div class="vc-menu-grid">
                        <div class="vc-menu-item" data-action="go_home">
                            <div class="vc-menu-item-icon">&#127968;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Trang chủ</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Trang chủ", "Về trang chủ"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_about">
                            <div class="vc-menu-item-icon">&#128214;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Giới thiệu</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Giới thiệu", "Về chúng tôi"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_news">
                            <div class="vc-menu-item-icon">&#128240;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Tin tức & Bài viết</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Tin tức", "Bài viết"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_contact">
                            <div class="vc-menu-item-icon">&#9742;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Liên hệ hỗ trợ</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Liên hệ", "Chăm sóc khách hàng"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="go_account">
                            <div class="vc-menu-item-icon">&#128100;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Tài khoản</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Tài khoản", "Đăng nhập"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="history_back">
                            <div class="vc-menu-item-icon">&#11013;&#65039;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Quay lại trang trước</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Quay lại", "Trở về"</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nhóm 3: Điều khiển giao diện -->
                <div class="vc-menu-group">
                    <div class="vc-group-title">&#9881;&#65039; Điều khiển giao diện</div>
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
                                <div class="vc-menu-item-hint">🗣️ Nói: "Xuống cuối trang"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="reload_page">
                            <div class="vc-menu-item-icon">&#128260;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Làm mới trang</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Tải lại trang", "Làm mới"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="dark_mode">
                            <div class="vc-menu-item-icon">&#127769;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Bật / Tắt chế độ tối</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Chế độ tối", "Giao diện ban đêm"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="zoom_in">
                            <div class="vc-menu-item-icon">&#128269;&#10133;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Phóng to chữ</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Phóng to chữ"</div>
                            </div>
                        </div>
                        <div class="vc-menu-item" data-action="zoom_out">
                            <div class="vc-menu-item-icon">&#128269;&#10134;</div>
                            <div class="vc-menu-item-info">
                                <div class="vc-menu-item-name">Thu nhỏ chữ</div>
                                <div class="vc-menu-item-hint">🗣️ Nói: "Thu nhỏ chữ"</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Panel Footer Hints -->
        <div class="vc-hints">
            <span>💡 <strong>Alt+M</strong> nói lệnh • <strong>Alt+A</strong> trợ năng khiếm thị</span>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div id="vc-toast" class="vc-toast" role="alert"></div>

    <!-- Screen Reader Live Announcement Region (WCAG) -->
    <div id="vc-sr-live" class="vc-sr-only" aria-live="assertive" aria-atomic="true"></div>
</div>
