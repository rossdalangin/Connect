<?php
/**
 * Affiliate Management Engine
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Affiliates {
    public function __construct() {
        add_action( 'init', [ $this, 'register_affiliate_post_types' ] );
        add_action( 'wp_ajax_saas_get_affiliate_stats', [ $this, 'ajax_get_stats' ] );
        add_action( 'wp_ajax_saas_request_payout', [ $this, 'handle_payout_request' ] );
        add_action( 'wp_ajax_saas_process_payout', [ $this, 'handle_process_payout' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_admin_meta_boxes' ] );
    }

    public function register_affiliate_post_types() {
        // CPTs now handled centrally in post-types.php to avoid duplicates
    }

    /**
     * Calculate recurring commissions
     */
    public function record_referral_sale( $referrer_id, $order_amount, $order_id = 0 ) {
        $percentage = get_option('saas_affiliate_percentage') ?: 30;

        // Log the commission calculation for debugging
        $commission = round($order_amount * ($percentage / 100), 2);

        $total_earned = floatval(get_user_meta( $referrer_id, '_saas_affiliate_earned', true )) ?: 0;
        update_user_meta( $referrer_id, '_saas_affiliate_earned', round($total_earned + $commission, 2) );

        // Log as saas_commission post for dashboard history
        wp_insert_post([
            'post_type'   => 'saas_commission',
            'post_title'  => 'Commission for Order #' . $order_id,
            'post_status' => 'publish',
            'post_author' => $referrer_id,
            'meta_input'  => [
                '_saas_commission_amount' => $commission,
                '_saas_order_id'         => $order_id,
                '_saas_order_amount'     => $order_amount,
                '_saas_percentage'       => $percentage
            ]
        ]);
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

    public function handle_payout_request() {
        check_ajax_referer( 'saas_dashboard_nonce', 'security' );
        $user_id = get_current_user_id();
        $amount = floatval( $_POST['amount'] );
        $method = sanitize_text_field( $_POST['method'] );
        $email = sanitize_email( $_POST['email'] );

        $earned = get_user_meta( $user_id, '_saas_affiliate_earned', true ) ?: 0;

        if ( $amount < 50 ) {
            wp_send_json_error( 'Minimum payout is $50.00' );
        }

        if ( $amount > $earned ) {
            wp_send_json_error( 'Insufficient balance' );
        }

        $payout_id = wp_insert_post([
            'post_type'   => 'saas_payout',
            'post_title'  => 'Payout Request - ' . wp_get_current_user()->display_name,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        update_post_meta( $payout_id, '_amount', $amount );
        update_post_meta( $payout_id, '_method', $method );
        update_post_meta( $payout_id, '_method_email', $email );
        update_post_meta( $payout_id, '_status', 'pending' );

        // Deduct from balance
        update_user_meta( $user_id, '_saas_affiliate_earned', $earned - $amount );

        wp_send_json_success( 'Payout request submitted! Your balance has been updated.' );
    }

    public function handle_process_payout() {
        if(!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer( 'saas_dashboard_nonce', 'security' );

        $payout_id = intval($_POST['payout_id']);
        if (!$payout_id || get_post_type($payout_id) !== 'saas_payout') {
            wp_send_json_error('Invalid payout ID');
        }

        $current_status = get_post_meta($payout_id, '_status', true);
        if ($current_status === 'paid') {
            wp_send_json_error('This payout has already been processed.');
        }

        update_post_meta($payout_id, '_status', 'paid');
        update_post_meta($payout_id, '_paid_at', current_time('mysql'));

        // Log the event as a commission record with negative value or just as a transaction log?
        // For this system, we'll log it as a saas_commission entry with a negative amount to keep balances accurate in logs
        $payout = get_post($payout_id);
        $amount = get_post_meta($payout_id, '_amount', true);

        wp_insert_post([
            'post_type'   => 'saas_commission',
            'post_title'  => 'Payout Processed - Request #' . $payout_id,
            'post_status' => 'publish',
            'post_author' => $payout->post_author,
            'meta_input'  => [
                '_saas_commission_amount' => -$amount,
                '_saas_payout_id'        => $payout_id,
                '_saas_order_amount'     => 0,
                '_saas_percentage'       => 0
            ]
        ]);

        wp_send_json_success('Payout marked as paid successfully!');
    }

    public function add_admin_meta_boxes() {
        add_meta_box( 'saas_payout_details', 'Payout Management', [ $this, 'render_admin_meta_box' ], 'saas_payout', 'normal', 'high' );
    }

    public function render_admin_meta_box( $post ) {
        $amount = get_post_meta($post->ID, '_amount', true);
        $method = get_post_meta($post->ID, '_method', true);
        $email  = get_post_meta($post->ID, '_method_email', true);
        $status = get_post_meta($post->ID, '_status', true);
        $user   = get_userdata($post->post_author);
        ?>
        <div class="saas-payout-admin">
            <p><strong>Affiliate:</strong> <?php echo $user->display_name; ?> (ID: <?php echo $user->ID; ?>)</p>
            <p><strong>Requested Amount:</strong> $<?php echo number_format($amount, 2); ?></p>
            <p><strong>Method:</strong> <?php echo strtoupper($method); ?></p>
            <p><strong>Payment Account:</strong> <?php echo $email; ?></p>
            <p><strong>Current Status:</strong> <?php echo strtoupper($status); ?></p>

            <hr>
            <?php if($status === 'pending') : ?>
                <form action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST" id="saas-admin-payout-form">
                    <input type="hidden" name="action" value="saas_process_payout">
                    <input type="hidden" name="payout_id" value="<?php echo $post->ID; ?>">
                    <input type="hidden" name="security" value="<?php echo wp_create_nonce('saas_dashboard_nonce'); ?>">
                    <p>Mark this payout as completed after you have sent the funds manually.</p>
                    <button type="submit" class="button button-primary">Mark as PAID</button>
                </form>
                <script>
                    jQuery('#saas-admin-payout-form').on('submit', function(e) {
                        e.preventDefault();
                        if(!confirm('Have you manually sent the funds to the affiliate?')) return;
                        var $form = jQuery(this);
                        jQuery.post(ajaxurl, $form.serialize(), function(res) {
                            if(res.success) {
                                alert('Payout marked as paid!');
                                location.reload();
                            }
                        });
                    });
                </script>
            <?php else: ?>
                <p style="color:green; font-weight:bold;">✓ This payout has been processed.</p>
            <?php endif; ?>
        </div>
        <?php
    }
}
new Saas_Affiliates();
