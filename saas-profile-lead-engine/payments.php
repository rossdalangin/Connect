<?php
/**
 * Payment and Subscription Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Payments {
    public function __construct() {
        add_action( 'wp_ajax_saas_checkout', [ $this, 'handle_checkout' ] );
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
        $plan_id = sanitize_text_field( $_POST['plan_id'] );
        $gateway = sanitize_text_field( $_POST['gateway'] );

        if ( $gateway === 'stripe' ) {
            $secret_key = get_option('saas_stripe_secret_key');
            // Mock API call to Stripe
            $session = [ 'url' => 'https://checkout.stripe.com/pay/mock_session_id' ];

            // For Pro Plan checkouts in this elite system, we create a pending order
            $order_id = wp_insert_post([
                'post_type' => 'saas_order',
                'post_title' => 'Pending Order - ' . $plan_id,
                'post_status' => 'publish',
                'post_author' => get_current_user_id()
            ]);
            update_post_meta($order_id, '_saas_order_amount', $plan_id === 'pro' ? 19.00 : 49.00);
            update_post_meta($order_id, '_saas_order_status', 'pending');

            wp_send_json_success([ 'redirect_url' => $session['url'] ]);
        } elseif ( $gateway === 'paypal' ) {
            $paypal_email = get_option('saas_paypal_email');
            $paypal_url = "https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=" . urlencode($paypal_email) . "&item_name=" . urlencode($plan_id);
            wp_send_json_success([ 'redirect_url' => $paypal_url ]);
        }

        wp_send_json_error( 'Gateway not supported' );
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
        $user_id = $data['user_id'] ?? 0;
        $status  = $data['status'] ?? '';
        $plan    = $data['plan'] ?? 'pro';

        if ( $user_id && $status === 'succeeded' ) {
            update_user_meta( $user_id, '_saas_subscription_plan', $plan );
            update_user_meta( $user_id, '_saas_subscription_expiry', strtotime('+1 year') );
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
