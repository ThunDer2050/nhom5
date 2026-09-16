<?php
/**
 * Plugin Name: Đọc Bài Viết
 * Plugin URI:  https://github.com/ThunDer2050/nhom5
 * Description: Đọc nội dung bài viết bằng giọng tiếng Việt. Hỗ trợ Web Speech API và Google Translate TTS (miễn phí).
 * Version:     2.1.0
 * Author:      Nhóm 5
 * Author URI:  https://github.com/ThunDer2050
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: doc-bai-viet
 */

// Ngăn truy cập trực tiếp vào file PHP.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ============================================================
 * PHẦN 1: ĐỊNH NGHĨA HẰNG SỐ
 * ============================================================
 */
define( 'DBV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DBV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DBV_VERSION', '2.1.0' );

/**
 * ============================================================
 * PHẦN 2: NẠP CSS VÀ JAVASCRIPT CHO FRONTEND
 * ============================================================
 * Hook 'wp_enqueue_scripts' — nạp CSS/JS vào trang frontend.
 * Chỉ nạp khi đang ở trang bài viết đơn (is_singular('post')).
 *
 * Truyền cho JS:
 * - noiDung: nội dung bài viết (text thuần, đã strip HTML).
 * - ajaxUrl: URL của admin-ajax.php để gửi AJAX request.
 * - nonce: mã bảo mật cho AJAX request.
 */
function dbv_enqueue_assets() {
    if ( ! is_singular( 'post' ) ) {
        return;
    }

    // Nạp CSS.
    wp_enqueue_style(
        'dbv-style',
        DBV_PLUGIN_URL . 'assets/css/style.css',
        array(),
        DBV_VERSION
    );

    // Nạp JavaScript.
    wp_enqueue_script(
        'dbv-script',
        DBV_PLUGIN_URL . 'assets/js/script.js',
        array(),
        DBV_VERSION,
        true
    );

    // Lấy bài viết hiện tại.
    $post = get_post();
    if ( ! $post ) {
        return;
    }

    // Lấy và xử lý nội dung bài viết.
    $tieu_de  = get_the_title( $post->ID );
    $noi_dung = apply_filters( 'the_content', get_the_content( null, false, $post ) );
    $noi_dung = wp_strip_all_tags( $noi_dung );
    $noi_dung = preg_replace( '/\s+/', ' ', $noi_dung );
    $noi_dung = trim( $noi_dung );

    // Ghép tiêu đề và nội dung.
    $van_ban_doc = '';
    if ( ! empty( $tieu_de ) ) {
        $van_ban_doc = $tieu_de . '. ';
    }
    if ( ! empty( $noi_dung ) ) {
        $van_ban_doc .= $noi_dung;
    }

    /**
     * Truyền dữ liệu từ PHP sang JavaScript qua wp_localize_script().
     * - noiDung: nội dung bài viết (text thuần).
     * - ajaxUrl: URL AJAX endpoint của WordPress.
     * - nonce: mã bảo mật AJAX.
     */
    wp_localize_script( 'dbv-script', 'dbvData', array(
        'noiDung' => $van_ban_doc,
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'dbv_tts_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'dbv_enqueue_assets' );

/**
 * ============================================================
 * PHẦN 3: NẠP CSS CHO TRANG ADMIN
 * ============================================================
 * Hook 'admin_enqueue_scripts' — nạp CSS cho trang cài đặt plugin.
 * Chỉ nạp khi đang ở đúng trang cài đặt của plugin.
 */
function dbv_admin_enqueue_assets( $hook ) {
    if ( 'settings_page_doc-bai-viet' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'dbv-admin-style',
        DBV_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        DBV_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'dbv_admin_enqueue_assets' );

/**
 * ============================================================
 * PHẦN 4: ĐĂNG KÝ MENU ADMIN
 * ============================================================
 * Hook 'admin_menu' — thêm trang cài đặt plugin vào menu Settings.
 *
 * add_options_page() tạo một trang con dưới menu "Settings" (Cài đặt).
 */
function dbv_admin_menu() {
    add_options_page(
        'Đọc Bài Viết - Cài đặt',   // Tiêu đề trang.
        'Đọc Bài Viết',              // Tên menu.
        'manage_options',             // Quyền truy cập (chỉ admin).
        'doc-bai-viet',               // Slug (định danh trang).
        'dbv_settings_page'           // Hàm hiển thị nội dung.
    );
}
add_action( 'admin_menu', 'dbv_admin_menu' );

/**
 * ============================================================
 * PHẦN 5: HIỂN THỊ TRANG CÀI ĐẶT
 * ============================================================
 * Trang thông tin và hướng dẫn sử dụng plugin.
 * Không cần API Key vì sử dụng Google Translate TTS miễn phí.
 */
function dbv_settings_page() {
    ?>
    <div class="wrap">
        <!-- Tiêu đề trang -->
        <div class="dbv-admin-header">
            <h1>🔊 <?php echo esc_html__( 'Đọc Bài Viết - Cài đặt', 'doc-bai-viet' ); ?></h1>
            <p class="dbv-admin-desc">
                <?php echo esc_html__( 'Plugin đọc nội dung bài viết bằng giọng tiếng Việt — Hoàn toàn miễn phí.', 'doc-bai-viet' ); ?>
            </p>
        </div>

        <!-- Card: Trạng thái -->
        <div class="dbv-admin-card">
            <h2>✅ <?php echo esc_html__( 'Trạng thái Plugin', 'doc-bai-viet' ); ?></h2>
            <div class="dbv-status-ok">
                ✅ <?php echo esc_html__( 'Plugin đang hoạt động. Không cần cấu hình thêm.', 'doc-bai-viet' ); ?>
            </div>
            <p style="margin-top: 12px; color: #50575e;">
                <?php echo esc_html__( 'Plugin sử dụng Google Translate TTS — hoàn toàn miễn phí, không cần API Key.', 'doc-bai-viet' ); ?>
            </p>
        </div>

        <!-- Card: Cách hoạt động -->
        <div class="dbv-admin-card">
            <h2>ℹ️ <?php echo esc_html__( 'Cách hoạt động', 'doc-bai-viet' ); ?></h2>
            <div class="dbv-flow">
                <div class="dbv-flow-step">
                    <span class="dbv-flow-number">1</span>
                    <p><?php echo esc_html__( 'Plugin kiểm tra trình duyệt có giọng đọc tiếng Việt sẵn có không (Web Speech API).', 'doc-bai-viet' ); ?></p>
                </div>
                <div class="dbv-flow-step">
                    <span class="dbv-flow-number">2</span>
                    <p><?php echo esc_html__( 'Nếu CÓ → sử dụng giọng đọc trình duyệt (nhanh, không cần mạng).', 'doc-bai-viet' ); ?></p>
                </div>
                <div class="dbv-flow-step">
                    <span class="dbv-flow-number">3</span>
                    <p><?php echo esc_html__( 'Nếu KHÔNG → sử dụng Google Translate TTS (miễn phí, cần kết nối mạng).', 'doc-bai-viet' ); ?></p>
                </div>
                <div class="dbv-flow-step">
                    <span class="dbv-flow-number">4</span>
                    <p><?php echo esc_html__( 'Nội dung được chia thành các đoạn nhỏ, gửi qua server WordPress để lấy audio MP3 từ Google, rồi phát lần lượt.', 'doc-bai-viet' ); ?></p>
                </div>
            </div>
        </div>

        <!-- Card: Hướng dẫn sử dụng -->
        <div class="dbv-admin-card">
            <h2>📋 <?php echo esc_html__( 'Hướng dẫn sử dụng', 'doc-bai-viet' ); ?></h2>
            <ol class="dbv-steps">
                <li><?php echo esc_html__( 'Tạo hoặc mở một bài viết (Post) trên website.', 'doc-bai-viet' ); ?></li>
                <li><?php echo esc_html__( 'Truy cập bài viết ở frontend (trang xem bài viết).', 'doc-bai-viet' ); ?></li>
                <li><?php echo esc_html__( 'Khu vực "Đọc Bài Viết" sẽ tự động xuất hiện ở cuối nội dung.', 'doc-bai-viet' ); ?></li>
                <li><?php echo esc_html__( 'Nhấn nút "Đọc" để bắt đầu nghe bài viết bằng tiếng Việt.', 'doc-bai-viet' ); ?></li>
                <li><?php echo esc_html__( 'Sử dụng các nút Tạm dừng, Tiếp tục, Dừng để điều khiển.', 'doc-bai-viet' ); ?></li>
            </ol>

            <div class="dbv-note dbv-note-info">
                <strong><?php echo esc_html__( 'Shortcode:', 'doc-bai-viet' ); ?></strong>
                <?php echo esc_html__( 'Bạn cũng có thể dùng shortcode [doc_bai_viet] để đặt khu vực điều khiển ở vị trí tùy ý trong bài viết.', 'doc-bai-viet' ); ?>
            </div>
        </div>

        <!-- Card: Thông tin kỹ thuật -->
        <div class="dbv-admin-card">
            <h2>🔧 <?php echo esc_html__( 'Thông tin kỹ thuật', 'doc-bai-viet' ); ?></h2>
            <table class="widefat striped" style="max-width: 500px;">
                <tbody>
                    <tr>
                        <td><strong><?php echo esc_html__( 'Phiên bản', 'doc-bai-viet' ); ?></strong></td>
                        <td><?php echo esc_html( DBV_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo esc_html__( 'Chế độ TTS chính', 'doc-bai-viet' ); ?></strong></td>
                        <td><?php echo esc_html__( 'Web Speech API (trình duyệt)', 'doc-bai-viet' ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo esc_html__( 'Chế độ TTS dự phòng', 'doc-bai-viet' ); ?></strong></td>
                        <td><?php echo esc_html__( 'Google Translate TTS (miễn phí)', 'doc-bai-viet' ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo esc_html__( 'Ngôn ngữ đọc', 'doc-bai-viet' ); ?></strong></td>
                        <td><?php echo esc_html__( 'Tiếng Việt (vi)', 'doc-bai-viet' ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo esc_html__( 'Yêu cầu API Key', 'doc-bai-viet' ); ?></strong></td>
                        <td><?php echo esc_html__( 'Không', 'doc-bai-viet' ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo esc_html__( 'Chi phí', 'doc-bai-viet' ); ?></strong></td>
                        <td><?php echo esc_html__( 'Miễn phí hoàn toàn', 'doc-bai-viet' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * ============================================================
 * PHẦN 6: AJAX HANDLER — GOOGLE TRANSLATE TTS (MIỄN PHÍ)
 * ============================================================
 * Xử lý AJAX request từ JavaScript frontend.
 *
 * Luồng hoạt động:
 * 1. JS gửi AJAX POST với text cần đọc.
 * 2. PHP gọi Google Translate TTS endpoint (miễn phí, không cần API Key).
 * 3. Google trả về file audio MP3.
 * 4. PHP chuyển audio (base64) về cho JS phát.
 *
 * Tại sao phải proxy qua PHP?
 * - Gọi trực tiếp từ JS sẽ bị lỗi CORS (Cross-Origin Resource Sharing).
 * - Proxy qua PHP server giúp tránh lỗi CORS.
 * - Đồng thời ẩn các chi tiết kỹ thuật khỏi client.
 *
 * Google Translate TTS endpoint:
 * URL: https://translate.google.com/translate_tts
 * Params:
 *   - ie=UTF-8 (encoding)
 *   - q=TEXT (nội dung cần đọc, URL encoded)
 *   - tl=vi (ngôn ngữ: tiếng Việt)
 *   - client=tw-ob (client identifier)
 *   - idx=0 (chỉ số đoạn)
 *   - total=1 (tổng số đoạn)
 *   - textlen=LENGTH (độ dài text)
 *
 * Hook:
 * - wp_ajax_dbv_text_to_speech: cho user đã đăng nhập.
 * - wp_ajax_nopriv_dbv_text_to_speech: cho user chưa đăng nhập (khách).
 */
function dbv_ajax_text_to_speech() {
    // Bước 1: Xác thực nonce (mã bảo mật).
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'dbv_tts_nonce' ) ) {
        wp_send_json_error( array(
            'message' => 'Lỗi bảo mật. Vui lòng tải lại trang.',
        ) );
    }

    // Bước 2: Lấy text cần đọc từ request.
    $text = isset( $_POST['text'] ) ? wp_unslash( $_POST['text'] ) : '';

    // Chỉ loại bỏ thẻ HTML, giữ nguyên nội dung tiếng Việt.
    $text = wp_strip_all_tags( $text );
    $text = trim( $text );

    if ( empty( $text ) ) {
        wp_send_json_error( array(
            'message' => 'Không có nội dung để đọc.',
        ) );
    }

    // Giới hạn độ dài text (Google Translate TTS giới hạn ~200 ký tự/request).
    if ( mb_strlen( $text, 'UTF-8' ) > 200 ) {
        $text = mb_substr( $text, 0, 200, 'UTF-8' );
    }

    // Bước 3: Tạo URL gọi Google Translate TTS.
    $text_encoded = rawurlencode( $text );
    $text_len     = mb_strlen( $text, 'UTF-8' );

    $tts_url = 'https://translate.google.com/translate_tts?'
        . 'ie=UTF-8'
        . '&q=' . $text_encoded
        . '&tl=vi'
        . '&client=tw-ob'
        . '&idx=0'
        . '&total=1'
        . '&textlen=' . $text_len;

    // Bước 4: Gọi Google Translate TTS bằng wp_remote_get().
    $response = wp_remote_get( $tts_url, array(
        'timeout'    => 15,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'headers'    => array(
            'Referer' => 'https://translate.google.com/',
        ),
    ) );

    // Bước 5: Kiểm tra lỗi kết nối.
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array(
            'message' => 'Lỗi kết nối: ' . $response->get_error_message(),
        ) );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $audio_data    = wp_remote_retrieve_body( $response );

    // Bước 6: Kiểm tra response.
    if ( 200 !== $response_code || empty( $audio_data ) ) {
        wp_send_json_error( array(
            'message' => 'Không thể tải audio từ Google (HTTP ' . $response_code . '). Vui lòng thử lại.',
        ) );
    }

    // Bước 7: Trả về audio dạng base64 cho JavaScript.
    $audio_base64 = base64_encode( $audio_data );
    wp_send_json_success( array(
        'audio' => $audio_base64,
    ) );
}
// Đăng ký AJAX handler cho cả user đăng nhập và khách.
add_action( 'wp_ajax_dbv_text_to_speech', 'dbv_ajax_text_to_speech' );
add_action( 'wp_ajax_nopriv_dbv_text_to_speech', 'dbv_ajax_text_to_speech' );

/**
 * ============================================================
 * PHẦN 7: TẠO HTML CHO KHU VỰC ĐIỀU KHIỂN (FRONTEND)
 * ============================================================
 */
function dbv_render_player() {
    $html = '<div id="dbv-player" class="dbv-player">';

    // Tiêu đề.
    $html .= '<div class="dbv-header">';
    $html .= '<span class="dbv-icon">&#128264;</span> ';
    $html .= '<span class="dbv-title">' . esc_html__( 'Đọc Bài Viết', 'doc-bai-viet' ) . '</span>';
    $html .= '</div>';

    // Các nút điều khiển.
    $html .= '<div class="dbv-controls">';
    $html .= '<button type="button" id="dbv-btn-doc" class="dbv-btn dbv-btn-doc" title="' . esc_attr__( 'Đọc bài viết', 'doc-bai-viet' ) . '">';
    $html .= '&#9654; ' . esc_html__( 'Đọc', 'doc-bai-viet' );
    $html .= '</button>';

    $html .= '<button type="button" id="dbv-btn-tam-dung" class="dbv-btn dbv-btn-tam-dung" disabled title="' . esc_attr__( 'Tạm dừng', 'doc-bai-viet' ) . '">';
    $html .= '&#10074;&#10074; ' . esc_html__( 'Tạm dừng', 'doc-bai-viet' );
    $html .= '</button>';

    $html .= '<button type="button" id="dbv-btn-tiep-tuc" class="dbv-btn dbv-btn-tiep-tuc" disabled title="' . esc_attr__( 'Tiếp tục đọc', 'doc-bai-viet' ) . '">';
    $html .= '&#9654;&#9654; ' . esc_html__( 'Tiếp tục', 'doc-bai-viet' );
    $html .= '</button>';

    $html .= '<button type="button" id="dbv-btn-dung" class="dbv-btn dbv-btn-dung" disabled title="' . esc_attr__( 'Dừng hoàn toàn', 'doc-bai-viet' ) . '">';
    $html .= '&#9632; ' . esc_html__( 'Dừng', 'doc-bai-viet' );
    $html .= '</button>';
    $html .= '</div>';

    // Thanh tốc độ.
    $html .= '<div class="dbv-speed">';
    $html .= '<label for="dbv-speed-range">' . esc_html__( 'Tốc độ đọc:', 'doc-bai-viet' ) . ' </label>';
    $html .= '<input type="range" id="dbv-speed-range" min="0.5" max="2" step="0.1" value="1">';
    $html .= '<span id="dbv-speed-value">1.0x</span>';
    $html .= '</div>';

    // Trạng thái.
    $html .= '<div class="dbv-status">';
    $html .= esc_html__( 'Trạng thái:', 'doc-bai-viet' ) . ' ';
    $html .= '<span id="dbv-status-text">' . esc_html__( 'Đang chờ', 'doc-bai-viet' ) . '</span>';
    $html .= '</div>';

    // Thông báo (ẩn mặc định).
    $html .= '<div id="dbv-thong-bao" class="dbv-thong-bao" style="display:none;"></div>';

    $html .= '</div>';

    return $html;
}

/**
 * ============================================================
 * PHẦN 8: TỰ ĐỘNG HIỂN THỊ Ở CUỐI NỘI DUNG BÀI VIẾT
 * ============================================================
 */
function dbv_append_to_content( $content ) {
    if ( is_singular( 'post' ) && in_the_loop() && is_main_query() ) {
        $content .= dbv_render_player();
    }
    return $content;
}
add_filter( 'the_content', 'dbv_append_to_content' );

/**
 * ============================================================
 * PHẦN 9: ĐĂNG KÝ SHORTCODE [doc_bai_viet]
 * ============================================================
 */
function dbv_shortcode( $atts ) {
    if ( ! is_singular( 'post' ) ) {
        return '';
    }
    return dbv_render_player();
}
add_shortcode( 'doc_bai_viet', 'dbv_shortcode' );
