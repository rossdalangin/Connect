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
        add_action( 'admin_post_saas_populate_pro_content', [ $this, 'handle_populate_pro_content' ] );
    }

    public function handle_populate_pro_content() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die('Unauthorized');

        $features = [
            ['icon' => '🎯', 'title' => 'Lead Generation Funnel', 'desc' => 'Don\'t just list links. Capture leads directly in your bio with high-converting forms that sync with your CRM.'],
            ['icon' => '📳', 'title' => 'Elite Networking (vCard)', 'desc' => 'Share your contact info instantly at events. One tap, and you are saved in their phone. No app required.'],
            ['icon' => '📈', 'title' => 'Conversion Analytics', 'desc' => 'Track views, clicks, and actual lead conversion rates. Know exactly what drives revenue for your business.'],
            ['icon' => '🎨', 'title' => 'Elite Whitelabeling', 'desc' => 'Your brand is the star. Remove our logos and use your own custom domain for a truly professional presence.']
        ];

        $benefits = [
            'Niche-specific templates designed for coaches and consultants',
            'Smart device-based routing (iOS/Android/Desktop)',
            'Automated email auto-responders for new leads',
            'Sticky A/B testing to optimize your best offers'
        ];

        $testimonials = [
            ['name' => 'Sarah Jenkins', 'role' => 'Executive Coach', 'text' => 'I was losing clients because my bio was too cluttered. Since switching, my discovery call bookings have doubled.'],
            ['name' => 'Marcus Thorne', 'role' => 'Strategy Consultant', 'text' => 'The NFC card feature is a game-changer at networking events. It built instant authority for my brand.'],
            ['name' => 'Elena Rodriguez', 'role' => 'Real Estate Advisor', 'text' => 'Finally, a digital card that actually captures leads. The automated sync with HubSpot saves me hours every week.']
        ];

        $faqs = [
            ['q' => 'Is this better than a standard link-in-bio tool?', 'a' => 'Yes. Standard tools are just lists. We are a conversion system designed to capture contact info and build trust.'],
            ['q' => 'Can I use my own domain?', 'a' => 'Absolutely. Elite Pro users can map their own custom domain or subdomain (e.g., links.yourbrand.com).'],
            ['q' => 'How does the lead capture work?', 'a' => 'You can add a form block to your profile. All submissions are saved in your dashboard and can be sent to your CRM via webhooks.']
        ];

        $logos = [
            'https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg',
            'https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg',
            'https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg',
            'https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg'
        ];

        $pricing = [
            [
                'name' => 'Free', 'price' => '$0', 'period' => 'forever', 'cta' => 'Join for Free', 'link' => '/register', 'style' => 'light',
                'features' => ['1 Profile', 'Standard Blocks', 'Basic Analytics', 'Community Support']
            ],
            [
                'name' => 'Elite Pro', 'price' => '$19', 'period' => '/mo', 'cta' => 'Upgrade to Pro', 'link' => '/register?plan=pro', 'style' => 'featured', 'badge' => 'FOR THE ELITE 1%',
                'features' => ['Everything in Free', 'Unlimited Premium Blocks', 'Lead Generation CRM', 'Custom Domain Mapping', 'Whitelabel Branding', 'Priority Support']
            ]
        ];

        $how_it_works = [
            ['title' => 'Claim Your Link', 'desc' => 'Register your unique URL in seconds. My system makes it easy to claim your digital home.'],
            ['title' => 'Build Your Funnel', 'desc' => 'Drag and drop blocks designed for consultants. Video, forms, and trust elements in one place.'],
            ['title' => 'Launch & Help More People', 'desc' => 'Start capturing leads on autopilot. Turn your skills into a system that scales your impact.']
        ];

        $comparison = [
            ['label' => 'Lead Capture Forms', 'basic' => '✗ No', 'elite' => '✓ Integrated CRM'],
            ['label' => 'A/B Testing CTAs', 'basic' => '✗ No', 'elite' => '✓ Smart Logic'],
            ['label' => 'NFC Card Sync', 'basic' => '✗ No', 'elite' => '✓ Native Support'],
            ['label' => 'Custom Domains', 'basic' => '✗ No', 'elite' => '✓ Full CNAME Support'],
            ['label' => 'Consultant Branding', 'basic' => 'Basic', 'elite' => '✓ Premium Glassy/Luxury Themes']
        ];

        update_option('saas_home_title', 'Your "Link in Bio" is Leaking Leads. Here is the Fix.');
        update_option('saas_home_hero', 'As a consultant, you are working too hard to lose clients at the last step. Turn your digital identity into a high-converting funnel that captures leads and builds trust on autopilot.');
        update_option('saas_home_cta', 'Launch My Elite Profile');
        update_option('saas_home_founder_letter', "Hey, I am a consultant just like you. I know how hard you work to sharpen your skills and help your clients. You spend hours creating content and showing up for people. But I saw so many of us losing 90% of our social traffic because we were using 'link lists' instead of 'sales funnels.' That is why I built this. Not just to give you a link, but to give you a system that honors your hard work and actually grows your business. Let's help more people together.");
        update_option('saas_home_testimonials_title', 'What Elite Consultants Are Saying');
        update_option('saas_home_features', json_encode($features));
        update_option('saas_home_benefits', json_encode($benefits));
        update_option('saas_home_testimonials', json_encode($testimonials));
        update_option('saas_home_faq', json_encode($faqs));
        update_option('saas_home_trusted_logos', json_encode($logos));
        update_option('saas_home_pricing_json', json_encode($pricing));
        update_option('saas_home_how_it_works_json', json_encode($how_it_works));
        update_option('saas_home_comparison_json', json_encode($comparison));
        update_option('saas_about_vision', "We believe that every consultant deserves a digital identity that works as hard as they do. Our platform is built to bridge the gap between social media attention and business results.\n\nBuilt by consultants, for consultants, we understand the long hours you put into sharpening your skills. Our mission is to ensure those skills are represented by a world-class conversion funnel that honors your expertise.");
        update_option('saas_contact_title', "Let's Connect and Grow Together");
        update_option('saas_login_title', "Welcome Back, Elite");
        update_option('saas_register_title', "Start Your 60-Second Launch");
        update_option('saas_pricing_title', "Invest in Your Growth");

        wp_redirect( admin_url('admin.php?page=saas_settings&pro_content_applied=1') );
        exit;
    }

    public function enqueue_admin_styles() {
        wp_enqueue_style( 'saas-admin-css', plugin_dir_url( __FILE__ ) . 'admin.css' );
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.0.0', true );
    }

    public function handle_generate_pages() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die('Unauthorized');

        $pages = [
            'Home'      => 'Welcome to our platform.',
            'Dashboard' => '[saas_dashboard]',
            'Login'     => '[saas_login_form]',
            'Register'  => '[saas_register_form]',
            'Pricing'   => 'Check our plans',
            'Contact'   => 'Get in touch',
            'About'     => 'Learn about us',
            'Directory' => 'Meet our elite creators.',
            'Story Card' => 'Your vertical social card.',
        ];

        $home_id = 0;
        foreach ( $pages as $title => $content ) {
            $page = get_page_by_title($title);
            if ( ! $page ) {
                $id = wp_insert_post([
                    'post_title'   => $title,
                    'post_content' => $content,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ]);

                if ($title === 'Home') {
                    update_post_meta($id, '_wp_page_template', 'template-landing-page.php');
                    $home_id = $id;
                }
                if ($title === 'Login' || $title === 'Register') {
                    update_post_meta($id, '_wp_page_template', 'template-auth.php');
                }
                if ($title === 'Pricing') {
                    update_post_meta($id, '_wp_page_template', 'template-pricing.php');
                }
                if ($title === 'Contact') {
                    update_post_meta($id, '_wp_page_template', 'template-contact.php');
                }
                if ($title === 'About') {
                    update_post_meta($id, '_wp_page_template', 'template-about.php');
                }
                if ($title === 'Directory') {
                    update_post_meta($id, '_wp_page_template', 'template-directory.php');
                }
                if ($title === 'Story Card') {
                    update_post_meta($id, '_wp_page_template', 'template-story-card.php');
                }
                if ($title === 'Register') {
                    update_option('users_can_register', 1);
                }
            } else {
                if ($title === 'Home') $home_id = $page->ID;
            }
        }

        if ($home_id) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $home_id);
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
        $activity = $analytics->get_global_activity_over_time();
        $labels = array_column($activity, 'date');
        $views = array_column($activity, 'views');
        $clicks = array_column($activity, 'clicks');

        echo '<div class="saas-widget-content">';
        echo '<div style="display:flex; gap:20px; margin-bottom:20px;">';
        echo '<div><strong>Views:</strong><br>' . number_format($summary['views']) . '</div>';
        echo '<div><strong>Clicks:</strong><br>' . number_format($summary['clicks']) . '</div>';
        echo '<div><strong>Leads:</strong><br>' . number_format($summary['leads']) . '</div>';
        echo '</div>';
        echo '<canvas id="saas-mini-chart" height="150"></canvas>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById("saas-mini-chart");
                if (ctx && typeof Chart !== "undefined") {
                    new Chart(ctx, {
                        type: "line",
                        data: {
                            labels: ' . json_encode($labels ?: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) . ',
                            datasets: [{
                                label: "Views",
                                data: ' . json_encode($views ?: [0,0,0,0,0,0,0]) . ',
                                borderColor: "#6c5ce7",
                                fill: true,
                                tension: 0.4
                            }, {
                                label: "Clicks",
                                data: ' . json_encode($clicks ?: [0,0,0,0,0,0,0]) . ',
                                borderColor: "#39e09b",
                                tension: 0.4
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                    });
                }
            });
        </script>';
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

        add_submenu_page(
            'saas_settings',
            'Financial Dashboard',
            'Finances',
            'manage_options',
            'saas_finances',
            [ $this, 'finances_page_html' ]
        );

        add_submenu_page(
            'saas_settings',
            'License Factory',
            'Licenses',
            'manage_options',
            'saas_license_factory',
            [ $this, 'license_factory_html' ]
        );
    }

    public function settings_init() {
        register_setting( 'saas_settings_group', 'saas_stripe_enabled' );
        register_setting( 'saas_settings_group', 'saas_stripe_secret_key' );
        register_setting( 'saas_settings_group', 'saas_paypal_enabled' );
        register_setting( 'saas_settings_group', 'saas_paypal_email' );
        register_setting( 'saas_settings_group', 'saas_affiliate_percentage' );
        register_setting( 'saas_settings_group', 'saas_global_logo' );

        // Homepage Content
        register_setting( 'saas_settings_group', 'saas_home_title' );
        register_setting( 'saas_settings_group', 'saas_home_hero' );
        register_setting( 'saas_settings_group', 'saas_home_cta' );
        register_setting( 'saas_settings_group', 'saas_home_founder_letter' );
        register_setting( 'saas_settings_group', 'saas_home_founder_image' );
        register_setting( 'saas_settings_group', 'saas_home_image' );
        register_setting( 'saas_settings_group', 'saas_home_faq' );
        register_setting( 'saas_settings_group', 'saas_home_testimonials' );
        register_setting( 'saas_settings_group', 'saas_home_trusted_logos' );
        register_setting( 'saas_settings_group', 'saas_home_benefits' );
        register_setting( 'saas_settings_group', 'saas_home_pricing_json' );
        register_setting( 'saas_settings_group', 'saas_home_how_it_works_json' );
        register_setting( 'saas_settings_group', 'saas_home_comparison_json' );
        register_setting( 'saas_settings_group', 'saas_home_testimonials_title' );
        register_setting( 'saas_settings_group', 'saas_login_title' );
        register_setting( 'saas_settings_group', 'saas_register_title' );
        register_setting( 'saas_settings_group', 'saas_about_vision' );
        register_setting( 'saas_settings_group', 'saas_pricing_title' );
        register_setting( 'saas_settings_group', 'saas_contact_title' );
        register_setting( 'saas_settings_group', 'saas_home_features' );

        add_settings_section(
            'saas_payment_section',
            'Payment Gateway Configuration',
            function() { echo '<p>Configure how you receive payments from Elite subscribers. <strong>Pro Tip:</strong> Enabling both Stripe and PayPal increases conversion by 15%.</p>'; },
            'saas_settings'
        );

        add_settings_field(
            'home_founder_image',
            'Founder Image URL',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_founder_image' ]
        );

        add_settings_field(
            'stripe_enabled',
            'Enable Stripe Payments',
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
            [ 'id' => 'saas_stripe_secret_key', 'desc' => 'Found in your Stripe Dashboard (Developers > API Keys).' ]
        );

        add_settings_section(
            'saas_branding_section',
            'Global Platform Branding',
            function() { echo '<p>These settings affect the main SaaS website and the administrative dashboard. User profiles have their own independent branding.</p>'; },
            'saas_settings'
        );

        add_settings_field(
            'global_logo',
            'SaaS Logo URL',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_branding_section',
            [ 'id' => 'saas_global_logo', 'desc' => 'Recommended: PNG with transparent background, 200x50px.' ]
        );

        add_settings_field(
            'affiliate_percentage',
            'Default Affiliate Commission (%)',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_payment_section',
            [ 'id' => 'saas_affiliate_percentage', 'desc' => 'Percentage of sale given to referrer (e.g. 30)' ]
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
            'home_founder_letter',
            'Founder Letter',
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_founder_letter' ]
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
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_faq', 'desc' => 'JSON array of objects with "q" and "a" keys.' ]
        );

        add_settings_field(
            'home_pricing',
            'Pricing Table (JSON)',
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_pricing_json', 'desc' => 'JSON array of plan objects.' ]
        );

        add_settings_field(
            'home_how_it_works',
            'How It Works (JSON)',
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_how_it_works_json', 'desc' => 'JSON array of steps.' ]
        );

        add_settings_field(
            'home_comparison',
            'Comparison Table (JSON)',
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_comparison_json', 'desc' => 'JSON array of comparison rows.' ]
        );

        add_settings_field(
            'home_testimonials_title',
            'Testimonials Section Title',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_testimonials_title' ]
        );

        add_settings_field(
            'home_testimonials',
            'Homepage Testimonials (JSON)',
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_testimonials', 'desc' => 'JSON array of objects with "name", "role", and "text".' ]
        );

        add_settings_field(
            'home_trusted_logos',
            'Trusted Logos (JSON URL List)',
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_trusted_logos', 'desc' => 'JSON array of image URLs.' ]
        );

        add_settings_field(
            'home_benefits',
            'Homepage Benefits (JSON)',
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_benefits', 'desc' => 'JSON array of strings.' ]
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
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_features', 'desc' => 'JSON array of objects with "icon", "title", and "desc".' ]
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

        add_settings_section(
            'saas_health_section',
            'System Health Check',
            null,
            'saas_settings'
        );

        add_settings_field(
            'system_health',
            'SaaS Engine Status',
            [ $this, 'render_health_check' ],
            'saas_settings',
            'saas_health_section'
        );
    }

    public function render_health_check() {
        global $wpdb;
        $table = $wpdb->prefix . 'saas_analytics';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $theme_active = file_exists(get_theme_root() . '/saas-profile-theme/style.css');

        echo '<ul>';
        echo '<li>Analytics Table: ' . ($table_exists ? '<span style="color:green;">✓ Ready</span>' : '<span style="color:red;">✗ Missing</span>') . '</li>';
        echo '<li>Profile Theme: ' . ($theme_active ? '<span style="color:green;">✓ Detected</span>' : '<span style="color:red;">✗ Not found</span>') . '</li>';
        echo '<li>Database Mode: <span style="color:green;">✓ Multi-tenant isolated</span></li>';
        echo '</ul>';
    }

    public function checkbox_render( $args ) {
        $value = get_option( $args['id'] );
        echo '<input type="checkbox" name="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( 1, $value, false ) . ' />';
    }

    public function text_render( $args ) {
        $value = get_option( $args['id'] );
        $desc = $args['desc'] ?? '';
        echo '<input type="text" name="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
        if ($desc) echo '<p class="description">' . esc_html($desc) . '</p>';
    }

    public function textarea_render( $args ) {
        $value = get_option( $args['id'] );
        echo '<textarea name="' . esc_attr( $args['id'] ) . '" rows="5" class="large-text">' . esc_textarea( $value ) . '</textarea>';
    }

    public function add_user_columns( $columns ) {
        $columns['saas_plan'] = 'SaaS Plan';
        $columns['saas_earnings'] = 'Earnings';
        return $columns;
    }

    public function render_user_columns( $val, $column, $user_id ) {
        if ( $column === 'saas_plan' ) {
            $plan = get_user_meta($user_id, '_saas_subscription_plan', true) ?: 'Free';
            $color = ($plan === 'pro') ? '#39e09b' : '#666';
            return '<strong style="color:'.$color.';">'.strtoupper($plan).'</strong>';
        }
        if ( $column === 'saas_earnings' ) {
            $earned = get_user_meta($user_id, '_saas_affiliate_earned', true) ?: 0;
            return '$' . number_format($earned, 2);
        }
        return $val;
    }

    public function finances_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $payouts = get_posts(['post_type' => 'saas_payout', 'post_status' => 'any', 'numberposts' => -1]);
        $orders  = get_posts(['post_type' => 'saas_order', 'post_status' => 'any', 'numberposts' => -1]);
        ?>
        <div class="wrap saas-admin-wrapper">
            <h1>Financial & Affiliate Management</h1>
            <p>Monitor global revenue and process affiliate commission requests.</p>

            <div class="saas-tabs-container">
                <h2 class="nav-tab-wrapper">
                    <a href="#tab-payouts" class="nav-tab nav-tab-active">Affiliate Payouts</a>
                    <a href="#tab-orders" class="nav-tab">Customer Orders</a>
                </h2>

                <div id="tab-payouts" class="tab-content">
                    <h3>Pending & Recent Payouts</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Affiliate</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($payouts as $p):
                                $status = get_post_meta($p->ID, '_status', true);
                                $user = get_userdata($p->post_author);
                            ?>
                            <tr>
                                <td><strong><?php echo $user->display_name; ?></strong></td>
                                <td>$<?php echo number_format(get_post_meta($p->ID, '_amount', true), 2); ?></td>
                                <td><?php echo strtoupper(get_post_meta($p->ID, '_method', true)); ?> (<?php echo get_post_meta($p->ID, '_method_email', true); ?>)</td>
                                <td><span class="status-badge status-<?php echo $status; ?>"><?php echo strtoupper($status); ?></span></td>
                                <td><?php echo get_the_date('', $p->ID); ?></td>
                                <td>
                                    <?php if($status === 'pending'): ?>
                                        <a href="<?php echo get_edit_post_link($p->ID); ?>" class="button button-small">Process Payout</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="tab-orders" class="tab-content" style="display:none;">
                    <h3>Customer Order History</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Item</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $o):
                                $user = get_userdata($o->post_author);
                            ?>
                            <tr>
                                <td><strong><?php echo $user->display_name; ?></strong></td>
                                <td><?php echo $o->post_title; ?></td>
                                <td>$<?php echo number_format(get_post_meta($o->ID, '_saas_order_amount', true), 2); ?></td>
                                <td><?php echo strtoupper(get_post_meta($o->ID, '_saas_order_status', true)); ?></td>
                                <td><?php echo get_the_date('', $o->ID); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
        jQuery('.nav-tab').on('click', function(e) {
            e.preventDefault();
            jQuery('.nav-tab').removeClass('nav-tab-active');
            jQuery(this).addClass('nav-tab-active');
            jQuery('.tab-content').hide();
            jQuery(jQuery(this).attr('href')).show();
        });
        </script>
        <?php
    }

    public function license_factory_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $users = get_users(['fields' => ['ID', 'display_name']]);
        $licenses = get_posts(['post_type' => 'saas_license', 'post_status' => 'any', 'numberposts' => -1]);
        ?>
        <div class="wrap saas-admin-wrapper">
            <h1>License Factory</h1>
            <p>Generate unique ELITE licenses for promotional use or manual sales.</p>

            <div class="saas-admin-card" style="background:#fff; padding:20px; border-radius:8px; border:1px solid #ddd; margin-bottom:30px;">
                <h3>Generate New License</h3>
                <form id="saas-license-gen-form" style="display:flex; gap:10px; align-items: flex-end;">
                    <div class="field">
                        <label>Assign to User (Optional)</label><br>
                        <select name="user_id">
                            <option value="0">Unassigned (General)</option>
                            <?php foreach($users as $u): ?>
                                <option value="<?php echo $u->ID; ?>"><?php echo $u->display_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Plan Type</label><br>
                        <select name="plan">
                            <option value="pro">ELITE PRO</option>
                            <option value="agency">AGENCY UNLIMITED</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Expiry Date</label><br>
                        <input type="date" name="expiry">
                    </div>
                    <button type="submit" class="button button-primary">Generate Key</button>
                </form>
            </div>

            <h3>Active & Used Licenses</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>License Key</th>
                        <th>Target Plan</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($licenses as $l):
                        $user = get_userdata($l->post_author);
                        $status = get_post_meta($l->ID, '_saas_license_status', true);
                    ?>
                    <tr>
                        <td><code><?php echo $l->post_title; ?></code></td>
                        <td><?php echo strtoupper(get_post_meta($l->ID, '_saas_license_plan', true)); ?></td>
                        <td><?php echo $user ? $user->display_name : 'General'; ?></td>
                        <td><span class="status-badge status-<?php echo $status; ?>"><?php echo strtoupper($status); ?></span></td>
                        <td><?php
                            $exp = get_post_meta($l->ID, '_saas_license_expiry', true);
                            echo $exp ? date('M j, Y', $exp) : 'Never';
                        ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
        jQuery('#saas-license-gen-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = jQuery(this).find('button');
            $btn.prop('disabled', true).text('Generating...');
            jQuery.post(ajaxurl, jQuery(this).serialize() + '&action=saas_generate_license&security=<?php echo wp_create_nonce("saas_dashboard_nonce"); ?>', function(res) {
                if(res.success) {
                    alert('New License Created: ' + res.data.key);
                    location.reload();
                }
                $btn.prop('disabled', false).text('Generate Key');
            });
        });
        </script>
        <?php
    }

    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        if ( isset($_GET['pro_content_applied']) ) {
            echo '<div class="updated notice is-dismissible"><p>Elite Pro Copy has been applied to your homepage! 🚀</p></div>';
        }
        if ( isset($_GET['pages_generated']) ) {
            echo '<div class="updated notice is-dismissible"><p>System pages and templates generated successfully!</p></div>';
        }

        $analytics = new Saas_Analytics();
        $summary = $analytics->get_global_summary();
        $growth = $analytics->get_growth_data();
        $growth_labels = array_column($growth, 'month');
        $growth_counts = array_column($growth, 'count');
        ?>
        <div class="wrap saas-admin-wrapper">
            <div class="saas-admin-sidebar">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?php echo admin_url('edit.php?post_type=saas_profile'); ?>">Profiles</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=saas_lead'); ?>">Captured Leads</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=saas_license'); ?>">System Licenses</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=saas_order'); ?>">Sales/Orders</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=saas_payout'); ?>">Affiliate Payouts</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=saas_message'); ?>">System Messages</a></li>
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

            <div style="background:#fff; padding:30px; border-radius:12px; margin-bottom:40px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
                <h3>System Growth Trends</h3>
                <canvas id="saas-admin-chart" height="100"></canvas>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('saas-admin-chart');
                if (ctx && typeof Chart !== 'undefined') {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode($growth_labels ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']); ?>,
                            datasets: [{
                                label: 'New Profiles',
                                data: <?php echo json_encode($growth_counts ?: [0, 0, 0, 0, 0, 0]); ?>,
                                backgroundColor: '#6c5ce7'
                            }]
                        },
                        options: { responsive: true }
                    });
                }
            });
            </script>

            <form action="options.php" method="post">
                <?php
                settings_fields( 'saas_settings_group' );
                do_settings_sections( 'saas_settings' );
                submit_button( 'Save Global Settings' );
                ?>
            </form>

            <hr>
            <h2>High-Conversion Copy Setup</h2>
            <p>Populate your homepage with professional, world-class sales copy designed by elite marketers.</p>
            <a href="<?php echo admin_url('admin-post.php?action=saas_populate_pro_content'); ?>" class="button button-primary" style="background:#39e09b; border-color:#39e09b; color:#1e2329;">Apply Pro Sales Copy</a>

            <hr>
            <h2>System Page Generator</h2>
            <p>Automatically create Login, Register, and Dashboard pages with correct shortcodes.</p>
            <a href="<?php echo admin_url('admin-post.php?action=saas_generate_pages'); ?>" class="button button-secondary">Generate System Pages</a>

            <hr>
            <h2>Sample Data Generator</h2>
            <p>Generate 5+ sample profiles (Coach, Realtor, Influencer) to test the system and demo to clients.</p>
            <button id="saas-generate-samples-btn" class="button button-secondary">Generate Sample Profiles</button>
            <button id="saas-test-payment-btn" class="button button-secondary" style="background:#f59e0b; color:#fff; border:none;">Simulate Success Payment (UID: 1)</button>

            <script>
            document.getElementById('saas-test-payment-btn')?.addEventListener('click', function() {
                if(!confirm('This will simulate a successful $19 payment for user ID 1. Continue?')) return;
                fetch('<?php echo get_rest_url(null, "/saas/v1/webhook"); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: 1, status: 'succeeded', plan: 'pro' })
                }).then(r => r.json()).then(d => {
                    alert('Webhook processed: ' + JSON.stringify(d));
                    location.reload();
                });
            });

            document.getElementById('saas-generate-samples-btn')?.addEventListener('click', function() {
                if (!confirm('This will create new sample profiles and links. Continue?')) return;
                const btn = this;
                btn.disabled = true;
                btn.innerText = 'Generating...';

                fetch(ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'saas_generate_samples'
                    })
                })
                .then(r => r.json())
                .then(data => {
                    alert(data.data);
                    location.reload();
                });
            });
            </script>

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

            <hr>
            <h2>System Broadcast</h2>
            <p>Send a message to every user's inbox in the system.</p>
            <form id="saas-broadcast-form">
                <p><input type="text" name="subject" placeholder="Broadcast Subject" style="width:100%;" required></p>
                <p><textarea name="message" placeholder="System update, promotion, or announcement..." style="width:100%;" rows="5" required></textarea></p>
                <p><button type="submit" class="button button-primary">Send to All Users</button></p>
            </form>
            <script>
            jQuery('#saas-broadcast-form').on('submit', function(e) {
                e.preventDefault();
                if(!confirm('This will send a message to EVERY user. Continue?')) return;
                var $btn = jQuery(this).find('button');
                $btn.prop('disabled', true).text('Sending...');
                jQuery.post(ajaxurl, jQuery(this).serialize() + '&action=saas_send_broadcast&security=<?php echo wp_create_nonce("saas_dashboard_nonce"); ?>', function(res) {
                    alert(res.data);
                    $btn.prop('disabled', false).text('Send to All Users');
                    if(res.success) jQuery('#saas-broadcast-form').find('input, textarea').val('');
                });
            });
            </script>
            </div>
        </div>
        <?php
    }
}
new Saas_Admin_Settings();
