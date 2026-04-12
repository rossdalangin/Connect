<?php
/**
 * Affiliate Management Engine
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Affiliates {
    public function __construct() {
        add_action( 'init', [ $this, 'register_affiliate_post_types' ] );
        add_action( 'wp_ajax_saas_get_affiliate_stats', [ $this, 'ajax_get_stats' ] );
    }

    public function register_affiliate_post_types() {
        register_post_type( 'saas_payout', [
            'labels' => ['name' => 'Payouts', 'singular_name' => 'Payout'],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-money-alt',
            'supports' => ['title', 'author'],
        ]);
    }

    /**
     * Calculate recurring commissions (30%)
     */
    public function record_referral_sale( $referrer_id, $order_amount ) {
        $commission = $order_amount * 0.30;
        $total_earned = get_user_meta( $referrer_id, '_saas_affiliate_earned', true ) ?: 0;
        update_user_meta( $referrer_id, '_saas_affiliate_earned', $total_earned + $commission );
    }

    public function ajax_get_stats() {
        $user_id = get_current_user_id();
        $earned = get_user_meta( $user_id, '_saas_affiliate_earned', true ) ?: 0;
        $refs = count(get_users(['meta_key' => '_saas_referred_by', 'meta_value' => $user_id]));

        wp_send_json_success([
            'earned' => number_format($earned, 2),
            'refs'   => $refs
        ]);
    }
}
new Saas_Affiliates();
