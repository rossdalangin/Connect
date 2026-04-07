<?php
/**
 * Admin Settings Implementation (WordPress Settings API)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Admin_Settings {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_filter( 'manage_users_columns', [ $this, 'add_user_columns' ] );
        add_filter( 'manage_users_custom_column', [ $this, 'render_user_columns' ], 10, 3 );
        add_action( 'admin_init', [ $this, 'settings_init' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
        add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
        add_action( 'admin_post_saas_generate_pages', [ $this, 'handle_generate_pages' ] );
    }

    public function enqueue_admin_styles() {
        wp_enqueue_style( 'saas-admin-css', plugin_dir_url( __FILE__ ) . 'admin.css' );
    }

    public function handle_generate_pages() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die('Unauthorized');

        $pages = [
            'Dashboard' => '[saas_dashboard]',
            'Login'     => '[saas_login_form]',
            'Register'  => '[saas_register_form]',
            'Pricing'   => 'Check our plans',
            'Contact'   => 'Get in touch',
            'About'     => 'Learn about us',
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

        // Homepage Content
        register_setting( 'saas_settings_group', 'saas_home_title' );
        register_setting( 'saas_settings_group', 'saas_home_hero' );
        register_setting( 'saas_settings_group', 'saas_home_cta' );
        register_setting( 'saas_settings_group', 'saas_home_image' );
        register_setting( 'saas_settings_group', 'saas_home_faq' );
        register_setting( 'saas_settings_group', 'saas_home_testimonials' );
        register_setting( 'saas_settings_group', 'saas_home_trusted_logos' );
        register_setting( 'saas_settings_group', 'saas_home_benefits' );
        register_setting( 'saas_settings_group', 'saas_login_title' );
        register_setting( 'saas_settings_group', 'saas_register_title' );
        register_setting( 'saas_settings_group', 'saas_about_vision' );
        register_setting( 'saas_settings_group', 'saas_pricing_title' );
        register_setting( 'saas_settings_group', 'saas_contact_title' );
        register_setting( 'saas_settings_group', 'saas_home_features' );

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
            'saas_branding_section',
            'Global Branding',
            null,
            'saas_settings'
        );

        add_settings_field(
            'global_logo',
            'SaaS Logo URL',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_branding_section',
            [ 'id' => 'saas_global_logo' ]
        );

        add_settings_section(
            'saas_homepage_section',
            'Homepage Content Editor',
            null,
            'saas_settings'
        );

        add_settings_field(
            'home_title',
            'Homepage Title',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_title' ]
        );

        add_settings_field(
            'home_hero',
            'Hero Description',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_hero' ]
        );

        add_settings_field(
            'home_cta',
            'CTA Button Text',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_cta' ]
        );

        add_settings_field(
            'home_image',
            'Hero Image URL',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_image' ]
        );

        add_settings_field(
            'home_faq',
            'Homepage FAQ (JSON)',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_faq' ]
        );

        add_settings_field(
            'home_testimonials',
            'Homepage Testimonials (JSON)',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_testimonials' ]
        );

        add_settings_field(
            'home_trusted_logos',
            'Trusted Logos (JSON URL List)',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_trusted_logos' ]
        );

        add_settings_field(
            'home_benefits',
            'Homepage Benefits (JSON)',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_benefits' ]
        );

        add_settings_field(
            'login_title',
            'Login Page Title',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_login_title' ]
        );

        add_settings_field(
            'register_title',
            'Register Page Title',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_register_title' ]
        );

        add_settings_field(
            'about_vision',
            'About Us Vision Text',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_about_vision' ]
        );

        add_settings_field(
            'pricing_title',
            'Pricing Page Title',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_pricing_title' ]
        );

        add_settings_field(
            'contact_title',
            'Contact Page Title',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_contact_title' ]
        );

        add_settings_field(
            'home_features',
            'Homepage Features (JSON)',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_features' ]
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

    public function add_user_columns( $columns ) {
        $columns['saas_plan'] = 'SaaS Plan';
        return $columns;
    }

    public function render_user_columns( $val, $column, $user_id ) {
        if ( $column === 'saas_plan' ) {
            $plan = get_user_meta($user_id, '_saas_subscription_plan', true) ?: 'Free';
            $color = ($plan === 'pro') ? '#39e09b' : '#666';
            return '<strong style="color:'.$color.';">'.strtoupper($plan).'</strong>';
        }
        return $val;
    }

    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $analytics = new Saas_Analytics();
        $summary = $analytics->get_global_summary();
        ?>
        <div class="wrap saas-admin-wrapper">
            <div class="saas-admin-sidebar">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?php echo admin_url('edit.php?post_type=saas_profile'); ?>">Profiles</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=saas_lead'); ?>">Captured Leads</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=saas_license'); ?>">System Licenses</a></li>
                </ul>
            </div>
            <div class="saas-admin-main">
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
        </div>
        <?php
    }
}
new Saas_Admin_Settings();
