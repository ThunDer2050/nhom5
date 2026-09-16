<?php
/**
 * Plugin Name: Voice Commerce AI
 * Plugin URI:  https://github.com/ThunDer2050/nhom5
 * Description: Nhận diện giọng nói thông minh tích hợp Google Gemini AI và Web Speech API, hỗ trợ điều khiển website, mua sắm và chuyển trang.
 * Version:     1.2.0
 * Author:      Nhom 5
 * License:     GPL-2.0+
 * Text Domain: voice-commerce
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'VC_VERSION',    '1.2.0' );
define( 'VC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once VC_PLUGIN_DIR . 'includes/class-voice-commands.php';
require_once VC_PLUGIN_DIR . 'includes/class-gemini-service.php';
require_once VC_PLUGIN_DIR . 'includes/class-ajax-handler.php';

class Voice_Commerce {
    private static $instance = null;
    private $ajax_handler;

    public static function get_instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->ajax_handler = new VC_Ajax_Handler();
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer',          [ $this, 'render_voice_button' ] );
        add_action( 'admin_menu',         [ $this, 'add_settings_page' ] );
        add_action( 'admin_init',         [ $this, 'register_settings' ] );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ),
                    [ $this, 'plugin_action_links' ] );
    }

    public function enqueue_assets() {
        if ( ! get_option( 'vc_enabled', 1 ) ) return;

        wp_enqueue_style(
            'voice-commerce-ui',
            VC_PLUGIN_URL . 'assets/css/voice-ui.css',
            [],
            VC_VERSION
        );
        wp_enqueue_script(
            'voice-commerce-engine',
            VC_PLUGIN_URL . 'assets/js/voice-engine.js',
            [ 'jquery' ],
            VC_VERSION,
            true
        );

        $woo = class_exists( 'WooCommerce' );
        $shop_id = $woo ? wc_get_page_id( 'shop' ) : 0;
        $about_page = get_page_by_path( 'gioi-thieu' );
        $contact_page = get_page_by_path( 'lien-he' );

        wp_localize_script( 'voice-commerce-engine', 'VoiceCommerce', [
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'voice_commerce_nonce' ),
            'homeUrl'       => home_url( '/' ),
            'shopUrl'       => $shop_id ? get_permalink( $shop_id ) : home_url( '/shop/' ),
            'cartUrl'       => $woo ? wc_get_cart_url()     : home_url( '/cart/' ),
            'checkoutUrl'   => $woo ? wc_get_checkout_url() : home_url( '/checkout/' ),
            'accountUrl'    => $woo ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
            'aboutUrl'      => $about_page ? get_permalink( $about_page->ID ) : home_url( '/gioi-thieu/' ),
            'contactUrl'    => $contact_page ? get_permalink( $contact_page->ID ) : home_url( '/lien-he/' ),
            'newsUrl'       => home_url( '/?cat=1' ),
            'isWooActive'   => $woo,
            'currentPostId' => get_the_ID(),
            'searchUrl'     => home_url( '/?s=' ),
            'language'      => get_option( 'vc_language', 'vi-VN' ),
            'buttonPos'     => get_option( 'vc_button_position', 'bottom-right' ),
            'hasGemini'     => ! empty( get_option( 'vc_gemini_api_key' ) ),
            'i18n'          => [
                'listening'    => 'Đang lắng nghe bạn nói...',
                'processing'   => 'Gemini AI đang xử lý...',
                'notSupported' => 'Trình duyệt chưa hỗ trợ nhận dạng giọng nói.',
                'micDenied'    => 'Vui lòng cấp quyền micro cho trình duyệt.',
                'noCommand'    => 'Chưa nhận rõ lệnh. Bạn thử nói lại nhé!',
                'pressToSpeak' => 'Nhấn để nói (Alt+M)',
            ],
        ] );
    }

    public function render_voice_button() {
        if ( ! get_option( 'vc_enabled', 1 ) ) return;
        include VC_PLUGIN_DIR . 'templates/voice-button.php';
    }

    public function add_settings_page() {
        add_options_page(
            'Voice Commerce AI Cài đặt',
            'Voice Commerce AI',
            'manage_options',
            'voice-commerce',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'vc_settings_group', 'vc_language' );
        register_setting( 'vc_settings_group', 'vc_button_position' );
        register_setting( 'vc_settings_group', 'vc_enabled' );
        register_setting( 'vc_settings_group', 'vc_gemini_api_key' );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $lang    = get_option( 'vc_language', 'vi-VN' );
        $pos     = get_option( 'vc_button_position', 'bottom-right' );
        $enabled = get_option( 'vc_enabled', 1 );
        $api_key = get_option( 'vc_gemini_api_key', 'AIzaSyAnbAhVU0e3Il3BI6-aIrX5HkDYUENrNfc' );
        ?>
        <div class="wrap">
            <h1>&#127908; Voice Commerce AI Settings (Nhóm 5)</h1>
            <p>Plugin điều khiển bằng giọng nói kết hợp công nghệ Web Speech API & <strong>Google Gemini AI (3.5 Flash Lite)</strong>.</p>
            <form method="post" action="options.php">
                <?php settings_fields( 'vc_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Kích hoạt Plugin</th>
                        <td>
                            <label>
                                <input type="checkbox" name="vc_enabled" value="1" <?php checked( 1, $enabled ); ?> />
                                Hiển thị widget micro trên toàn website
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Google Gemini API Key</th>
                        <td>
                            <input type="password" name="vc_gemini_api_key" value="<?php echo esc_attr( $api_key ); ?>" style="width:400px;font-family:monospace;" />
                            <p class="description">API Key từ Google AI Studio (Model: <code>gemini-3.5-flash-lite</code>) để phân tích ngôn ngữ tự nhiên.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Ngôn ngữ nhận diện</th>
                        <td>
                            <select name="vc_language">
                                <option value="vi-VN" <?php selected( $lang, 'vi-VN' ); ?>>Tiếng Việt (vi-VN)</option>
                                <option value="en-US" <?php selected( $lang, 'en-US' ); ?>>English (en-US)</option>
                                <option value="auto"  <?php selected( $lang, 'auto' ); ?>>Tự động</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Vị trí nút Micro</th>
                        <td>
                            <select name="vc_button_position">
                                <option value="bottom-right" <?php selected( $pos, 'bottom-right' ); ?>>Góc phải dưới màn hình</option>
                                <option value="bottom-left"  <?php selected( $pos, 'bottom-left' ); ?>>Góc trái dưới màn hình</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Lưu cài đặt' ); ?>
            </form>
            <hr/>
            <h2>📋 Danh sách Thao tác & Lệnh Giọng nói Hỗ trợ</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th style="width:180px;">Danh mục</th>
                        <th>Câu lệnh mẫu (Nói tự nhiên)</th>
                        <th>Hành động thực hiện</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>🛒 Mua sắm</strong></td>
                        <td>"Tìm bánh tét lá cẩm", "Cho vào giỏ 2 đòn bánh tét", "Xem giỏ hàng", "Thanh toán", "Xóa giỏ hàng"</td>
                        <td>Tìm kiếm theo tên đặc sản, thêm vào giỏ, điều hướng giỏ hàng và thanh toán.</td>
                    </tr>
                    <tr>
                        <td><strong>🧭 Điều hướng</strong></td>
                        <td>"Về trang chủ", "Vào cửa hàng", "Xem giới thiệu", "Trang tin tức", "Liên hệ hỗ trợ", "Tài khoản của tôi", "Quay lại"</td>
                        <td>Chuyển tức thì đến các trang tương ứng trên website.</td>
                    </tr>
                    <tr>
                        <td><strong>📜 Cuộn & Trang</strong></td>
                        <td>"Lên đầu trang", "Xuống cuối trang", "Cuộn xuống", "Cuộn lên", "Tải lại trang"</td>
                        <td>Cuộn mượt mà trang web theo lệnh hoặc làm mới trang.</td>
                    </tr>
                    <tr>
                        <td><strong>🎨 Giao diện & Trợ năng</strong></td>
                        <td>"Bật chế độ tối" (hoặc "Giao diện tối"), "Đọc nội dung trang", "Dừng đọc", "Phóng to chữ", "Thu nhỏ chữ"</td>
                        <td>Bật/tắt Dark mode, đọc văn bản trang bằng Text-To-Speech tiếng Việt, chỉnh cỡ chữ.</td>
                    </tr>
                    <tr>
                        <td><strong>🤖 Trợ lý Gemini AI</strong></td>
                        <td>"Đặc sản Cần Thơ có gì ngon?", "Bánh tét lá cẩm giá bao nhiêu?"</td>
                        <td>Gemini AI tư vấn trực tiếp và đọc câu trả lời bằng giọng nói.</td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:15px;"><strong>💡 Phím tắt nhanh:</strong> Bấm <code>Alt + M</code> trên bàn phím để bật / tắt micro mọi lúc.</p>
        </div>
        <?php
    }

    public function plugin_action_links( $links ) {
        $url = admin_url( 'options-general.php?page=voice-commerce' );
        array_unshift( $links, '<a href="' . esc_url( $url ) . '">Cài đặt</a>' );
        return $links;
    }
}

register_activation_hook( __FILE__, function() {
    add_option( 'vc_enabled', 1 );
    add_option( 'vc_language', 'vi-VN' );
    add_option( 'vc_button_position', 'bottom-right' );
    add_option( 'vc_gemini_api_key', 'AIzaSyAnbAhVU0e3Il3BI6-aIrX5HkDYUENrNfc' );
} );

add_action( 'plugins_loaded', function() {
    Voice_Commerce::get_instance();
} );
