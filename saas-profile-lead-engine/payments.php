<?php
/**
 * Payment and Subscription Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Payments {
    public function __construct() {
        add_action( 'wp_ajax_saas_checkout', [ $this, 'handle_checkout' ] );
        add_action( 'wp_ajax_saas_cancel_subscription', [ $this, 'handle_cancel_subscription' ] );
    }

    /**
     * Get Active Gateway (Dynamic Logic)
     */
    public function get_active_gateway() {
        $stripe_enabled = get_option( 'saas_stripe_enabled' );
        $paypal_enabled = get_option( 'saas_paypal_enabled' );

        if ( $stripe_enabled && $paypal_enabled ) {
            return 'user_select';
        } elseif ( $stripe_enabled ) {
            return 'stripe';
        } elseif ( $paypal_enabled ) {
            return 'paypal';
        }
        return 'none';
    }

    /**
     * Check if user has Pro features
     */
    public function is_pro_user( $user_id ) {
        $plan = get_user_meta( $user_id, '_saas_subscription_plan', true );
        $expiry = get_user_meta( $user_id, '_saas_subscription_expiry', true );

        if ( $plan == 'pro' && ( ! $expiry || $expiry > time() ) ) {
            return true;
        }
        return false;
    }

    /**
     * AJAX: Process Checkout Session
     */
    public function handle_checkout() {
        check_ajax_referer( 'saas_dashboard_nonce', 'security' );

        $plan_id = sanitize_text_field( $_POST['plan_id'] );
        $gateway = sanitize_text_field( $_POST['gateway'] );
        $coupon_code = isset($_POST['coupon']) ? strtoupper(sanitize_text_field($_POST['coupon'])) : '';
        $user_id = get_current_user_id();
        $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;

        $amount = 19.00;
        $is_product = false;

        if (strpos($plan_id, 'product_') === 0) {
            $is_product = true;
            $amount = floatval(get_post_meta($block_id, '_saas_price', true));
            if (!$amount) $amount = 99.00; // Fallback
        } elseif ($plan_id === 'agency') {
            $amount = 49.00;
        }

        // Apply Affiliate Coupon Discount
        $discount_pct = 0;
        $affiliate_id = 0;
        if ($coupon_code) {
            $coupons = get_option('saas_affiliate_coupons') ?: [];
            foreach ($coupons as $c) {
                if (strtoupper($c['code']) === $coupon_code) {
                    $discount_pct = floatval($c['discount']);
                    $affiliate_id = intval($c['user_id']);
                    break;
                }
            }
        }

        if ($discount_pct > 0) {
            $amount = $amount * (1 - ($discount_pct / 100));
            // If a coupon is used, it sets/overrides the referrer
            if ($affiliate_id) {
                update_user_meta($user_id, '_saas_referred_by', $affiliate_id);
            }
        }

        if ( $gateway === 'stripe' ) {
            $secret_key = get_option('saas_stripe_secret_key');
            // Mock API call to Stripe
            $session = [ 'url' => 'https://checkout.stripe.com/pay/mock_session_id' ];

            // For Pro Plan checkouts in this elite system, we create a pending order
            $order_id = wp_insert_post([
                'post_type' => 'saas_order',
                'post_title' => ($is_product ? 'Product Sale: ' : 'Plan Upgrade: ') . $plan_id,
                'post_status' => 'publish',
                'post_author' => $is_product ? get_post_field('post_author', $block_id) : $user_id
            ]);
            update_post_meta($order_id, '_saas_order_amount', $amount);
            update_post_meta($order_id, '_saas_order_status', 'pending');
            if ($is_product) {
                update_post_meta($order_id, '_saas_product_id', $block_id);
                update_post_meta($order_id, '_saas_customer_id', $user_id);
                update_post_meta($order_id, '_saas_is_direct_sale', 1);
            }

            wp_send_json_success([ 'redirect_url' => $session['url'] . '?order_id=' . $order_id ]);
        } elseif ( $gateway === 'paypal' ) {
            $paypal_email = get_option('saas_paypal_email');
            $paypal_url = "https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=" . urlencode($paypal_email) . "&item_name=" . urlencode($plan_id);
            wp_send_json_success([ 'redirect_url' => $paypal_url ]);
        }

        wp_send_json_error( 'Gateway not supported' );
    }

    public function handle_cancel_subscription() {
        check_ajax_referer( 'saas_dashboard_nonce', 'security' );
        $user_id = get_current_user_id();

        update_user_meta( $user_id, '_saas_subscription_plan', 'free' );
        update_user_meta( $user_id, '_saas_subscription_expiry', time() ); // Expire immediately

        wp_send_json_success( 'Subscription cancelled successfully.' );
    }

    /**
     * REST: Webhook Handler (Stripe/PayPal)
     */
    public function register_webhook_route() {
        register_rest_route( 'saas/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [ $this, 'process_webhook' ],
            'permission_callback' => '__return_true',
        ]);
    }

    public function process_webhook( $request ) {
        $data = $request->get_json_params();

        // Mock verification logic
        $user_id  = $data['user_id'] ?? 0;
        $status   = $data['status'] ?? '';
        $plan     = $data['plan'] ?? 'pro';
        $order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;

        if ( $status === 'succeeded' ) {
            $amount = 19.00;
            if ($order_id) {
                update_post_meta($order_id, '_saas_order_status', 'completed');
                $amount = get_post_meta($order_id, '_saas_order_amount', true) ?: 19.00;
            }

            if ($user_id) {
                update_user_meta( $user_id, '_saas_subscription_plan', $plan );
                update_user_meta( $user_id, '_saas_subscription_expiry', strtotime('+1 year') );

                // Handle Affiliate Commission
                $referrer_id = get_user_meta($user_id, '_saas_referred_by', true);
                if ($referrer_id) {
                    $aff = new Saas_Affiliates();
                    $aff->record_referral_sale($referrer_id, floatval($amount));
                }
            }

            return new WP_REST_Response( [ 'success' => true ], 200 );
        }

        return new WP_REST_Response( [ 'error' => 'Invalid webhook payload' ], 400 );
    }
}
add_action( 'rest_api_init', function() {
    $p = new Saas_Payments();
    $p->register_webhook_route();
});
new Saas_Payments();
