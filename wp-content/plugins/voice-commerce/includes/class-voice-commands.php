<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VC_Voice_Commands {

    private static $commands = [
        'search'         => [ ['tim kiem','tim','tra cuu','tim san pham'], ['search','find','look for','search for'] ],
        'add_to_cart'    => [ ['them vao gio','them vao gio hang','cho vao gio','dat mua','mua ngay'], ['add to cart','add to basket','buy this','purchase'] ],
        'view_cart'      => [ ['xem gio hang','gio hang','mo gio hang','kiem tra gio'], ['view cart','open cart','show cart','my cart'] ],
        'checkout'       => [ ['thanh toan','dat hang','tien hanh thanh toan','hoan tat don hang','tinh tien'], ['checkout','place order','proceed to checkout','complete order','pay now'] ],
        'clear_cart'     => [ ['xoa gio hang','lam trong gio','xoa gio','huy gio hang'], ['clear cart','empty cart','delete cart'] ],
        'go_home'        => [ ['trang chu','ve trang chu','di den trang chu','quay ve'], ['go home','home page','homepage','back to home'] ],
        'go_shop'        => [ ['cua hang','xem san pham','den cua hang','trang cua hang','san pham','mua sam'], ['shop','go to shop','view products','product page'] ],
        'go_about'       => [ ['gioi thieu','ve chung toi','thong tin cua hang','trang gioi thieu'], ['about','about us','about shop'] ],
        'go_news'        => [ ['tin tuc','bai viet','doc tin','blog','trang tin tuc'], ['news','blog','posts','articles'] ],
        'go_contact'     => [ ['lien he','ho tro','cham soc khach hang','dia chi'], ['contact','contact us','support','hotline'] ],
        'go_account'     => [ ['tai khoan','tai khoan cua toi','dang nhap','dang ky'], ['my account','account','login','register'] ],
        'scroll_top'     => [ ['len dau trang','ve dau trang','len tren cung','dau trang'], ['scroll to top','top of page','go to top','page top'] ],
        'scroll_bottom'  => [ ['xuong cuoi trang','ve cuoi trang','xuong duoi cung','cuoi trang'], ['scroll to bottom','bottom of page','go to bottom'] ],
        'scroll_down'    => [ ['cuon xuong','keo xuong','xuong duoi','luot xuong'], ['scroll down','page down','go down'] ],
        'scroll_up'      => [ ['cuon len','keo len','len tren','luot len'], ['scroll up','page up','go up'] ],
        'reload_page'    => [ ['tai lai trang','tai lai','lam moi','f5'], ['reload page','refresh page','reload','refresh'] ],
        'history_back'   => [ ['quay lai','tro ve','trang truoc'], ['go back','back','previous page'] ],
        'dark_mode'      => [ ['che do toi','giao dien toi','bat che do toi','tat che do toi','che do ban dem','giao dien sang'], ['dark mode','night mode','toggle dark mode','light mode'] ],
        'read_page'      => [ ['doc noi dung','doc bai viet','doc trang','doc van ban'], ['read page','read content','speak content','read article'] ],
        'stop_reading'   => [ ['dung doc','ngung doc','ngat giong'], ['stop reading','pause reading','stop speech'] ],
        'zoom_in'        => [ ['phong to chu','chu to hon','tang co chu','phong to'], ['zoom in','larger text','increase font'] ],
        'zoom_out'       => [ ['thu nho chu','chu nho hon','giam co chu','thu nho'], ['zoom out','smaller text','decrease font'] ],
        'zoom_reset'     => [ ['co chu chuan','chu binh thuong','khoi phuc co chu'], ['reset zoom','normal text','reset font'] ],
        'accessibility_mode' => [ ['che do khiem thi','khiem thi','ho tro khiem thi','tro nang','che do nguoi mu','tat che do khiem thi','bat che do khiem thi','che do tiep can'], ['accessibility mode','blind mode','accessibility','assistive mode'] ],
        'read_products'      => [ ['doc san pham','danh sach san pham','doc cac san pham','doc hang hoa','co nhung san pham nao'], ['read products','list products','speak products'] ],
        'cart_summary'       => [ ['kiem tra gio hang','doc gio hang','co bao nhieu mon','tong tien gio hang','kiem tra gio'], ['cart summary','check cart','how many items'] ],
        'high_contrast'      => [ ['tuong phan cao','che do tuong phan','do tuong phan cao','mau tuong phan'], ['high contrast','contrast mode','toggle contrast'] ],
        'stop'               => [ ['dung lai','dung','tat','thoi','im lang'], ['stop','stop listening','cancel','quit','silence'] ],
    ];

    public static function parse( string $transcript ): array {
        $lower = mb_strtolower( trim( $transcript ) );
        $ascii = self::remove_accents( $lower );

        $best_match = null;
        $best_len   = 0;

        foreach ( self::$commands as $action => $lang_groups ) {
            foreach ( $lang_groups as $phrases ) {
                foreach ( $phrases as $phrase ) {
                    $phrase_ascii = self::remove_accents( $phrase );
                    $match_pos = strpos( $ascii, $phrase_ascii );
                    if ( $match_pos !== false ) {
                        $len = strlen( $phrase_ascii );
                        if ( $len > $best_len ) {
                            $best_len = $len;
                            $keyword = ( 'search' === $action || 'add_to_cart' === $action )
                                ? trim( substr( $lower, $match_pos + strlen( $phrase ) ) )
                                : null;
                            $best_match = [
                                'action'   => $action,
                                'keyword'  => $keyword,
                                'quantity' => 1,
                                'matched'  => $phrase
                            ];
                        }
                    }
                }
            }
        }

        return $best_match ?? [ 'action' => 'unknown', 'keyword' => null, 'quantity' => 1, 'matched' => null ];
    }

    public static function remove_accents( string $str ): string {
        $accents = [
            'a' => ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ'],
            'e' => ['è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ'],
            'i' => ['ì','í','ị','ỉ','ĩ'],
            'o' => ['ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ'],
            'u' => ['ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ'],
            'y' => ['ỳ','ý','ỵ','ỷ','ỹ'],
            'd' => ['đ'],
            'A' => ['À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ'],
            'E' => ['È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ'],
            'I' => ['Ì','Í','Ị','Ỉ','Ĩ'],
            'O' => ['Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ'],
            'U' => ['Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ'],
            'Y' => ['Ỳ','Ý','Ỵ','Ỷ','Ỹ'],
            'D' => ['Đ'],
        ];
        foreach ( $accents as $non_accent => $accent_list ) {
            $str = str_replace( $accent_list, $non_accent, $str );
        }
        return $str;
    }
}
