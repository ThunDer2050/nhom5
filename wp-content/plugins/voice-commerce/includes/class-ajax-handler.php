<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once VC_PLUGIN_DIR . 'includes/class-gemini-service.php';

class VC_Ajax_Handler {

    public function __construct() {
        $actions = [
            'vc_search',
            'vc_add_to_cart',
            'vc_clear_cart',
            'vc_get_product',
            'vc_parse_command',
            'vc_ai_command'
        ];
        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_' . $action,        [ $this, 'handle_' . str_replace( 'vc_', '', $action ) ] );
            add_action( 'wp_ajax_nopriv_' . $action, [ $this, 'handle_' . str_replace( 'vc_', '', $action ) ] );
        }
    }

    private function verify_nonce(): void {
        if ( ! check_ajax_referer( 'voice_commerce_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
        }
    }

    /**
     * AI Command Processor powered by Google Gemini AI
     */
    public function handle_ai_command(): void {
        $this->verify_nonce();
        $transcript = sanitize_text_field( $_POST['transcript'] ?? '' );
        if ( empty( $transcript ) ) {
            wp_send_json_error( [ 'message' => 'Transcript required.' ] );
        }

        // 1. Try Gemini AI analysis
        $ai_result = VC_Gemini_Service::analyze_command( $transcript );

        // 2. If AI fails, use local regex parser fallback
        if ( ! $ai_result['success'] ) {
            $local = VC_Voice_Commands::parse( $transcript );
            $ai_result = [
                'success'  => true,
                'action'   => $local['action'],
                'keyword'  => $local['keyword'] ?? '',
                'quantity' => 1,
                'reply'    => 'Đã nhận lệnh: ' . $transcript,
                'is_local' => true
            ];
        }

        $action   = $ai_result['action'];
        $keyword  = $ai_result['keyword'] ?? '';
        $quantity = max( 1, intval( $ai_result['quantity'] ?? 1 ) );
        $reply    = $ai_result['reply'] ?? '';
        $extra    = [];

        // If action is add_to_cart with a product name, resolve the product in WooCommerce
        if ( 'add_to_cart' === $action && class_exists( 'WooCommerce' ) ) {
            $target_id = 0;
            if ( ! empty( $keyword ) ) {
                $args = [
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    's'              => $keyword,
                    'posts_per_page' => 1
                ];
                $query = new WP_Query( $args );
                if ( $query->have_posts() ) {
                    $target_id = $query->posts[0]->ID;
                }
            }

            // Fallback to current post if on product page
            if ( ! $target_id && ! empty( $_POST['current_post_id'] ) ) {
                $target_id = intval( $_POST['current_post_id'] );
            }

            if ( $target_id ) {
                $product = wc_get_product( $target_id );
                if ( $product && $product->is_purchasable() ) {
                    WC()->cart->add_to_cart( $target_id, $quantity );
                    WC()->cart->calculate_totals();
                    $extra['product_name'] = $product->get_name();
                    $extra['cart_count']   = WC()->cart->get_cart_contents_count();
                    $reply = "Đã thêm {$quantity} {$product->get_name()} vào giỏ hàng.";
                }
            }
        }

        // If action is search, pre-fetch results
        if ( 'search' === $action && ! empty( $keyword ) ) {
            $search_res = $this->search_products( $keyword );
            $extra['search_results'] = $search_res;
        }

        wp_send_json_success( array_merge( [
            'action'   => $action,
            'keyword'  => $keyword,
            'quantity' => $quantity,
            'reply'    => $reply,
        ], $extra ) );
    }

    /**
     * Live search handler
     */
    public function handle_search(): void {
        $this->verify_nonce();
        $keyword = sanitize_text_field( $_POST['keyword'] ?? '' );
        if ( empty( $keyword ) ) wp_send_json_error( [ 'message' => 'Keyword required.' ] );

        $results = $this->search_products( $keyword );

        wp_send_json_success( [
            'keyword'    => $keyword,
            'results'    => $results,
            'count'      => count( $results ),
            'search_url' => home_url( '/?s=' . urlencode( $keyword ) ),
        ] );
    }

    private function search_products( string $keyword ): array {
        $results = [];
        $woo_active = class_exists( 'WooCommerce' );

        if ( $woo_active ) {
            $products = wc_get_products( [ 'status' => 'publish', 's' => $keyword, 'limit' => 5 ] );
            foreach ( $products as $p ) {
                $thumb = get_the_post_thumbnail_url( $p->get_id(), 'thumbnail' );
                if ( empty( $thumb ) ) {
                    $thumb = wc_placeholder_img_src();
                }
                $results[] = [
                    'id'    => $p->get_id(),
                    'title' => $p->get_name(),
                    'url'   => get_permalink( $p->get_id() ),
                    'price' => $p->get_price_html(),
                    'image' => $thumb,
                    'type'  => 'product',
                ];
            }
        }

        if ( empty( $results ) ) {
            $q = new WP_Query( [ 's' => $keyword, 'posts_per_page' => 5, 'post_status' => 'publish' ] );
            foreach ( $q->posts as $post ) {
                $results[] = [
                    'id'    => $post->ID,
                    'title' => $post->post_title,
                    'url'   => get_permalink( $post->ID ),
                    'price' => '',
                    'image' => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: '',
                    'type'  => 'post',
                ];
            }
        }

        return $results;
    }

    /**
     * Add to Cart handler
     */
    public function handle_add_to_cart(): void {
        $this->verify_nonce();
        if ( ! class_exists( 'WooCommerce' ) ) wp_send_json_error( [ 'message' => 'WooCommerce chua hoat dong.' ] );

        $product_id = intval( $_POST['product_id'] ?? 0 );
        $quantity   = max( 1, intval( $_POST['quantity'] ?? 1 ) );

        if ( ! $product_id ) wp_send_json_error( [ 'message' => 'Product ID required.' ] );

        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->is_purchasable() ) {
            wp_send_json_error( [ 'message' => 'San pham khong kha dung.' ] );
        }

        $added = WC()->cart->add_to_cart( $product_id, $quantity );
        if ( $added ) {
            WC()->cart->calculate_totals();
            wp_send_json_success( [
                'message'    => 'Da them vao gio hang.',
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_url'   => wc_get_cart_url(),
                'product'    => $product->get_name(),
            ] );
        } else {
            wp_send_json_error( [ 'message' => 'Khong the them vao gio.' ] );
        }
    }

    /**
     * Clear Cart handler
     */
    public function handle_clear_cart(): void {
        $this->verify_nonce();
        if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
            wp_send_json_error( [ 'message' => 'WooCommerce chua hoat dong.' ] );
        }

        WC()->cart->empty_cart();
        wp_send_json_success( [
            'message'    => 'Da xoa sach gio hang.',
            'cart_count' => 0
        ] );
    }

    public function handle_get_product(): void {
        $this->verify_nonce();
        $product_id = intval( $_POST['product_id'] ?? 0 );
        if ( ! $product_id ) wp_send_json_error( [ 'message' => 'Product ID required.' ] );

        $product = wc_get_product( $product_id );
        if ( ! $product ) wp_send_json_error( [ 'message' => 'San pham khong ton tai.' ] );

        wp_send_json_success( [
            'id'             => $product->get_id(),
            'name'           => $product->get_name(),
            'price_html'     => $product->get_price_html(),
            'in_stock'       => $product->is_in_stock(),
            'is_purchasable' => $product->is_purchasable(),
            'url'            => get_permalink( $product->get_id() ),
        ] );
    }

    public function handle_parse_command(): void {
        $this->verify_nonce();
        $transcript = sanitize_text_field( $_POST['transcript'] ?? '' );
        if ( empty( $transcript ) ) wp_send_json_error( [ 'message' => 'Transcript required.' ] );
        wp_send_json_success( VC_Voice_Commands::parse( $transcript ) );
    }
}
