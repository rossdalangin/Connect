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
        update_post_meta($payout_id, '_status', 'paid');
        wp_send_json_success('Payout marked as paid.');
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
