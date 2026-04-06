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
            wp_send_json_success([ 'redirect_url' => $session['url'] ]);
        } elseif ( $gateway === 'paypal' ) {
            $paypal_email = get_option('saas_paypal_email');
            $paypal_url = "https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=" . urlencode($paypal_email) . "&item_name=" . urlencode($plan_id);
            wp_send_json_success([ 'redirect_url' => $paypal_url ]);
        }

        wp_send_json_error( 'Gateway not supported' );
    }

    /**
     * Webhook Handler (Stripe/PayPal)
     */
    public static function handle_webhooks() {
        // logic for processing payment confirmations
        // update_user_meta( $user_id, '_saas_subscription_plan', 'pro' );
    }
}
new Saas_Payments();
