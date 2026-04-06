<?php
/**
 * Admin Settings Implementation (WordPress Settings API)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Admin_Settings {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'settings_init' ] );
    }

    public function add_admin_menu() {
        add_menu_page(
            'SaaS System Settings',
            'SaaS Settings',
            'manage_options',
            'saas_settings',
            [ $this, 'settings_page_html' ],
            'dashicons-admin-generic'
        );
    }

    public function settings_init() {
        register_setting( 'saas_settings_group', 'saas_stripe_enabled' );
        register_setting( 'saas_settings_group', 'saas_stripe_secret_key' );
        register_setting( 'saas_settings_group', 'saas_paypal_enabled' );
        register_setting( 'saas_settings_group', 'saas_paypal_email' );
        register_setting( 'saas_settings_group', 'saas_global_logo' );

        add_settings_section(
            'saas_payment_section',
            'Payment Gateway Configuration',
            null,
            'saas_settings'
        );

        add_settings_field(
            'stripe_enabled',
            'Enable Stripe',
            [ $this, 'checkbox_render' ],
            'saas_settings',
            'saas_payment_section',
            [ 'id' => 'saas_stripe_enabled' ]
        );

        add_settings_field(
            'stripe_secret',
            'Stripe Secret Key',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_payment_section',
            [ 'id' => 'saas_stripe_secret_key' ]
        );
    }

    public function checkbox_render( $args ) {
        $value = get_option( $args['id'] );
        echo '<input type="checkbox" name="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( 1, $value, false ) . ' />';
    }

    public function text_render( $args ) {
        $value = get_option( $args['id'] );
        echo '<input type="text" name="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $analytics = new Saas_Analytics();
        $summary = $analytics->get_global_summary();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <!-- Global Analytics Dashboard Card -->
            <div class="saas-admin-card" style="background:#fff; padding:20px; border-radius:8px; display:flex; gap:40px; margin:20px 0;">
                <div><strong>Total Views:</strong> <br> <span style="font-size:2rem;"><?php echo number_format($summary['views']); ?></span></div>
                <div><strong>Total Clicks:</strong> <br> <span style="font-size:2rem;"><?php echo number_format($summary['clicks']); ?></span></div>
                <div><strong>Total Leads:</strong> <br> <span style="font-size:2rem;"><?php echo number_format($summary['leads']); ?></span></div>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'saas_settings_group' );
                do_settings_sections( 'saas_settings' );
                submit_button( 'Save Global Settings' );
                ?>
            </form>
        </div>
        <?php
    }
}
new Saas_Admin_Settings();
