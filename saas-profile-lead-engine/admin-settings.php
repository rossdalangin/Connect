<?php
/**
 * Admin Settings Implementation (WordPress Settings API)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Admin_Settings {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'settings_init' ] );
        add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
        add_action( 'admin_post_saas_load_samples', [ $this, 'handle_load_samples' ] );
        add_action( 'admin_post_saas_generate_pages', [ $this, 'handle_generate_pages' ] );
    }

    public function handle_generate_pages() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die('Unauthorized');

        $pages = [
            'Dashboard' => '[saas_dashboard]',
            'Login'     => '[saas_login_form]',
            'Register'  => '[saas_register_form]',
        ];

        foreach ( $pages as $title => $content ) {
            if ( ! get_page_by_title($title) ) {
                wp_insert_post([
                    'post_title'   => $title,
                    'post_content' => $content,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ]);
            }
        }

        wp_redirect( admin_url('admin.php?page=saas_settings&pages_generated=1') );
        exit;
    }

    public function handle_load_samples() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die('Unauthorized');
        require_once plugin_dir_path( __FILE__ ) . 'sample-data.php';
        Saas_Sample_Data::generate();
        wp_redirect( admin_url('admin.php?page=saas_settings&samples_loaded=1') );
        exit;
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'saas_admin_summary_widget',
            'SaaS System Overview',
            [ $this, 'render_dashboard_widget' ]
        );
    }

    public function render_dashboard_widget() {
        $analytics = new Saas_Analytics();
        $summary = $analytics->get_global_summary();
        echo '<div class="saas-widget-content">';
        echo '<p><strong>Total Page Views:</strong> ' . number_format($summary['views']) . '</p>';
        echo '<p><strong>Total Link Clicks:</strong> ' . number_format($summary['clicks']) . '</p>';
        echo '<p><strong>Total Leads Captured:</strong> ' . number_format($summary['leads']) . '</p>';
        echo '<hr><p><a href="'.admin_url('admin.php?page=saas_settings').'" class="button button-primary">SaaS Settings</a></p>';
        echo '</div>';
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

        add_settings_section(
            'saas_license_section',
            'License Management',
            null,
            'saas_settings'
        );

        add_settings_field(
            'global_license_status',
            'System License Status',
            function() { echo '<strong>Active (Enterprise)</strong>'; },
            'saas_settings',
            'saas_license_section'
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

            <?php if ( isset($_GET['samples_loaded']) ) echo '<div class="updated"><p>Sample data generated successfully!</p></div>'; ?>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'saas_settings_group' );
                do_settings_sections( 'saas_settings' );
                submit_button( 'Save Global Settings' );
                ?>
            </form>

            <hr>
            <h2>Sample Data Generator</h2>
            <p>Click below to populate your site with elite sample profiles, links, leads, and analytics for testing.</p>
            <a href="<?php echo admin_url('admin-post.php?action=saas_load_samples'); ?>" class="button button-secondary">Generate Sample Data (10+ Records)</a>

            <hr>
            <h2>System Page Generator</h2>
            <p>Automatically create Login, Register, and Dashboard pages with correct shortcodes.</p>
            <a href="<?php echo admin_url('admin-post.php?action=saas_generate_pages'); ?>" class="button button-primary">Generate System Pages</a>

            <hr>
            <h2>User Level Setup Guide</h2>
            <div style="background:#f9f9f9; padding:20px; border-radius:8px; border-left:4px solid #0073aa;">
                <ol>
                    <li><strong>Admin:</strong> Generate system pages using the button above.</li>
                    <li><strong>User:</strong> Register an account on the /register page.</li>
                    <li><strong>User:</strong> Login and navigate to /dashboard.</li>
                    <li><strong>User:</strong> Set up your profile (username, bio, links).</li>
                    <li><strong>User:</strong> Share your unique link (domain.com/username).</li>
                </ol>
            </div>
        </div>
        <?php
    }
}
new Saas_Admin_Settings();
