<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VC_Gemini_Service {

    private static $model = 'gemini-3.5-flash-lite';

    public static function get_api_key(): string {
        return get_option( 'vc_gemini_api_key', 'AIzaSyAnbAhVU0e3Il3BI6-aIrX5HkDYUENrNfc' );
    }

    public static function analyze_command( string $transcript, array $context = [] ): array {
        $api_key = trim( self::get_api_key() );
        if ( empty( $api_key ) ) {
            return [ 'success' => false, 'error' => 'API Key not configured' ];
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/" . self::$model . ":generateContent?key=" . $api_key;

        $available_products = $context['products'] ?? 'Bánh tét lá cẩm Cần Thơ, Rượu mận Sáu Tia, Nem nướng Cái Răng, Trà mãng cầu Cần Thơ, Bánh tráng Thuận Hưng, Khô cá lóc đồng miền Tây';

        $system_instruction = "Bạn là trợ lý ảo bằng giọng nói thông minh cho website thương mại điện tử 'Đặc sản Cần Thơ - Nhóm 5'.
Người dùng sẽ nói một câu bằng tiếng Việt hoặc tiếng Anh.
Nhiệm vụ của bạn là phân tích câu nói và trích xuất DUY NHẤT một chuỗi JSON hợp lệ không bọc thêm bất kỳ văn bản nào ngoài JSON.

Danh sách các action hợp lệ:
- 'search': tìm kiếm sản phẩm. Trả về 'keyword' (tên sản phẩm tìm kiếm).
- 'add_to_cart': thêm sản phẩm vào giỏ hàng. Trả về 'keyword' (tên sản phẩm) và 'quantity' (số lượng, mặc định 1).
- 'view_cart': xem giỏ hàng.
- 'checkout': tiến hành đặt hàng / thanh toán.
- 'clear_cart': xóa sạch giỏ hàng.
- 'go_home': về trang chủ.
- 'go_shop': mở trang cửa hàng / danh sách sản phẩm.
- 'go_about': xem trang giới thiệu.
- 'go_news': xem tin tức / bài viết.
- 'go_contact': xem thông tin liên hệ.
- 'go_account': mở trang tài khoản / đăng nhập.
- 'scroll_top': cuộn lên đầu trang.
- 'scroll_bottom': cuộn xuống cuối trang.
- 'scroll_down': cuộn xuống.
- 'scroll_up': cuộn lên.
- 'reload_page': tải lại trang / làm mới.
- 'history_back': quay lại trang trước.
- 'dark_mode': bật / tắt chế độ tối (dark mode / giao diện tối).
- 'read_page': đọc to nội dung trang cho người dùng nghe (Text to Speech).
- 'stop_reading': dừng đọc nội dung trang.
- 'zoom_in': phóng to cỡ chữ.
- 'zoom_out': thu nhỏ cỡ chữ.
- 'zoom_reset': cỡ chữ bình thường.
- 'toggle_menu': mở hoặc xem danh mục menu lệnh.
- 'accessibility_mode': bật hoặc tắt chế độ hỗ trợ người khiếm thị / khuyết tật (WCAG accessibility mode, đọc giọng nói khi di chuyển và tương phản cao).
- 'hands_free_mode': bật hoặc tắt chế độ rảnh tay / kích hoạt bằng từ khóa đánh thức 'Trợ lý ơi'.
- 'read_products': đọc to danh sách các sản phẩm và giá tiền trên màn hình cho người khiếm thị nghe.
- 'cart_summary': kiểm tra và đọc to số lượng món và tổng tiền giỏ hàng.
- 'high_contrast': bật hoặc tắt chế độ tương phản cao (vàng đen cho người thị lực kém).
- 'ai_chat': nếu người dùng hỏi thăm hoặc nhờ tư vấn về đặc sản Cần Thơ.

Các sản phẩm có sẵn: {$available_products}.

Định dạng JSON bắt buộc:
{
  \"action\": \"tên_action\",
  \"keyword\": \"từ khóa hoặc tên sản phẩm (nếu có)\",
  \"quantity\": 1,
  \"reply\": \"Câu trả lời ngắn gọn, thân thiện bằng tiếng Việt (dưới 25 từ) để trợ lý phát âm thanh cho khách nghe\"
}";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [ 'text' => $system_instruction . "\n\nNgười dùng vừa nói: \"" . addslashes( $transcript ) . "\"" ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 300
            ]
        ];

        $ch = curl_init( $endpoint );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $payload ) );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ] );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 6 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

        $response = curl_exec( $ch );
        $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( 200 !== $http_code || ! $response ) {
            return [ 'success' => false, 'error' => 'API response error (' . $http_code . ')' ];
        }

        $res_data = json_decode( $response, true );
        $text_output = $res_data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Clean markdown code blocks if any
        $cleaned = preg_replace( '/^```(?:json)?\s*/i', '', trim( $text_output ) );
        $cleaned = preg_replace( '/\s*```$/', '', $cleaned );

        $parsed = json_decode( $cleaned, true );
        if ( ! is_array( $parsed ) || empty( $parsed['action'] ) ) {
            return [ 'success' => false, 'raw' => $text_output ];
        }

        return [
            'success'  => true,
            'action'   => $parsed['action'],
            'keyword'  => $parsed['keyword'] ?? '',
            'quantity' => intval( $parsed['quantity'] ?? 1 ),
            'reply'    => $parsed['reply'] ?? ''
        ];
    }
}
