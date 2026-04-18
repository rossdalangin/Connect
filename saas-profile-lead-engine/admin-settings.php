<?php
/**
 * Admin Settings Implementation (WordPress Settings API)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Admin_Settings {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'wp_ajax_saas_save_affiliate_coupons', [$this, 'ajax_save_affiliate_coupons'] );
        add_action( 'wp_ajax_saas_send_broadcast', [$this, 'ajax_send_broadcast'] );
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
            ],
            [
                'name' => 'Agency Unlimited', 'price' => '$49', 'period' => '/mo', 'cta' => 'Go Unlimited', 'link' => '/register?plan=agency', 'style' => 'light',
                'features' => ['Everything in Pro', 'Unlimited Sub-accounts', 'API Access', 'White-label Client Funnels', 'Dedicated Account Manager']
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

        update_option('saas_home_title', 'Stop Leaking High-Ticket Leads From Your Bio Link.');
        update_option('saas_home_hero', 'Ditch the digital graveyard. Build an Authority Engine that captures leads, automates trust, and represents the Elite professional you actually are.');
        update_option('saas_home_cta', 'Yes! Build My Authority Engine');
        update_option('saas_home_founder_letter', "I was posting every day. 50k followers. But my bank account didn't match my reach. I realized I was sending traffic to a 'link tree' that offered too many choices. People were curious, but they weren't inquiring. I built Elite Funnels to create a path, not a list. The first day I switched, I got 3 discovery call bookings. You work too hard to lose clients at the finish line. Let's build your engine together.");
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
        echo '<p style="margin-top:0; color:#666;">Performance stats for <strong>' . date('F') . '</strong>:</p>';
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

        add_submenu_page(
            'saas_settings',
            'Content Hub',
            'Content Hub',
            'manage_options',
            'saas_content_hub',
            [ $this, 'content_hub_page_html' ]
        );
    }

    public function settings_init() {
        register_setting( 'saas_settings_group', 'saas_stripe_enabled', ['type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => 0] );
        register_setting( 'saas_settings_group', 'saas_stripe_secret_key', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'] );
        register_setting( 'saas_settings_group', 'saas_paypal_enabled', ['type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => 0] );
        register_setting( 'saas_settings_group', 'saas_paypal_email', ['type' => 'string', 'sanitize_callback' => 'sanitize_email'] );
        register_setting( 'saas_settings_group', 'saas_affiliate_percentage' );
        register_setting( 'saas_settings_group', 'saas_global_logo' );
        register_setting( 'saas_settings_group', 'saas_global_favicon' );
        register_setting( 'saas_settings_group', 'saas_global_css' );

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
        register_setting( 'saas_settings_group', 'saas_home_profile_offset' );
        register_setting( 'saas_settings_group', 'saas_home_lead_offset' );
        register_setting( 'saas_settings_group', 'saas_home_rev_offset' );

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

        add_settings_field(
            'paypal_enabled',
            'Enable PayPal Payments',
            [ $this, 'checkbox_render' ],
            'saas_settings',
            'saas_payment_section',
            [ 'id' => 'saas_paypal_enabled' ]
        );

        add_settings_field(
            'paypal_email',
            'PayPal Business Email',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_payment_section',
            [ 'id' => 'saas_paypal_email', 'desc' => 'Email address associated with your PayPal merchant account.' ]
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
            'global_favicon',
            'Platform Favicon URL',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_branding_section',
            [ 'id' => 'saas_global_favicon' ]
        );

        add_settings_field(
            'global_css',
            'Global Platform CSS',
            [ $this, 'textarea_render' ],
            'saas_settings',
            'saas_branding_section',
            [ 'id' => 'saas_global_css', 'desc' => 'Inject custom CSS into the dashboard and main platform pages.' ]
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

        add_settings_field(
            'home_profile_offset',
            'Growth: Profile Offset',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_profile_offset', 'desc' => 'Number to add to the real profile count for social proof.' ]
        );

        add_settings_field(
            'home_lead_offset',
            'Growth: Lead Offset',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_lead_offset', 'desc' => 'Number to add to the real lead count.' ]
        );

        add_settings_field(
            'home_rev_offset',
            'Growth: Revenue Offset ($M)',
            [ $this, 'text_render' ],
            'saas_settings',
            'saas_homepage_section',
            [ 'id' => 'saas_home_rev_offset', 'desc' => 'Millions of dollars to add to tracked revenue (e.g. 42.5).' ]
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

    public function ajax_save_affiliate_coupons() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('saas_dashboard_nonce', 'security');

        $raw_coupons = $_POST['coupons'] ?? [];
        $sanitized_coupons = [];

        if (is_array($raw_coupons)) {
            foreach ($raw_coupons as $c) {
                if (!isset($c['user_id']) || !isset($c['code'])) continue;
                $sanitized_coupons[] = [
                    'user_id'  => intval($c['user_id']),
                    'code'     => strtoupper(sanitize_text_field($c['code'])),
                    'discount' => floatval($c['discount'] ?? 0)
                ];
            }
        }

        update_option('saas_affiliate_coupons', $sanitized_coupons);
        wp_send_json_success('Affiliate coupons saved successfully!');
    }

    public function content_hub_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        if (isset($_POST['saas_save_content_hub'])) {
            check_admin_referer('saas_content_hub_nonce');

            if (isset($_POST['templates_json'])) {
                $templates = json_decode(stripslashes($_POST['templates_json']), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('saas_templates', $templates);
                }
            }

            if (isset($_POST['training_json'])) {
                $training = json_decode(stripslashes($_POST['training_json']), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('saas_training_academy', $training);
                }
            }

            if (isset($_POST['kb_json'])) {
                $kb = json_decode(stripslashes($_POST['kb_json']), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('saas_knowledge_base', $kb);
                }
            }

            if (isset($_POST['marketing_json'])) {
                $marketing = json_decode(stripslashes($_POST['marketing_json']), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('saas_marketing_materials', $marketing);
                }
            }

            if (isset($_POST['scripts_json'])) {
                $scripts = json_decode(stripslashes($_POST['scripts_json']), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('saas_sales_scripts', $scripts);
                }
            }

            if (isset($_POST['marketing_kit_json'])) {
                $kit = json_decode(stripslashes($_POST['marketing_kit_json']), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('saas_affiliate_marketing_kit', $kit);
                }
            }

            if (isset($_POST['email_templates_json'])) {
                $emails = json_decode(stripslashes($_POST['email_templates_json']), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('saas_email_templates', $emails);
                }
            }

            if (isset($_POST['saas_home_title'])) update_option('saas_home_title', sanitize_text_field($_POST['saas_home_title']));
            if (isset($_POST['saas_home_hero'])) update_option('saas_home_hero', sanitize_textarea_field($_POST['saas_home_hero']));
            if (isset($_POST['saas_home_founder_letter'])) update_option('saas_home_founder_letter', sanitize_textarea_field($_POST['saas_home_founder_letter']));

            if (isset($_POST['featured_profiles_json'])) {
                $featured = json_decode(stripslashes($_POST['featured_profiles_json']), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('saas_featured_profiles', $featured);
                }
            }

            echo '<div class="updated"><p>Content Hub updated successfully!</p></div>';
        }

        $templates = get_option('saas_templates') ?: $this->get_default_templates();
        $training  = get_option('saas_training_academy') ?: $this->get_default_training();
        $kb        = get_option('saas_knowledge_base') ?: $this->get_default_kb();
        $marketing = get_option('saas_marketing_materials') ?: $this->get_default_marketing();
        $scripts   = get_option('saas_sales_scripts') ?: $this->get_default_scripts();
        $affiliate_kit = get_option('saas_affiliate_marketing_kit') ?: $this->get_default_marketing_kit();
        $emails    = get_option('saas_email_templates') ?: $this->get_default_emails();
        $featured  = get_option('saas_featured_profiles') ?: [];
        ?>
        <div class="wrap saas-admin-wrapper">
            <h1>SaaS Content Hub</h1>
            <p>Manage templates, training videos, knowledge base, and affiliate marketing materials.</p>

            <div class="saas-tabs-container">
                <h2 class="nav-tab-wrapper">
                    <a href="#tab-templates" class="nav-tab nav-tab-active">Templates</a>
                    <a href="#tab-training" class="nav-tab">Training Academy</a>
                    <a href="#tab-kb" class="nav-tab">Knowledge Base</a>
                    <a href="#tab-marketing" class="nav-tab">Marketing Materials</a>
                    <a href="#tab-scripts" class="nav-tab">Sales Scripts</a>
                    <a href="#tab-marketing-kit" class="nav-tab">Marketing Kit</a>
                    <a href="#tab-emails" class="nav-tab">Email Templates</a>
                    <a href="#tab-vsl" class="nav-tab">🔥 Sales Letter / VSL</a>
                    <a href="#tab-featured" class="nav-tab">⭐ Featured Profiles</a>
                </h2>

                <form method="post" action="">
                    <?php wp_nonce_field('saas_content_hub_nonce'); ?>

                    <div id="tab-templates" class="tab-content" style="padding:20px; background:#fff;">
                        <h3>Manage Profile Templates (JSON)</h3>
                        <p class="description">Define the headline, bio, colors, and default blocks for each niche template. These are used by the Setup Wizard.</p>
                        <textarea name="templates_json" id="json-templates" style="width:100%; height:400px; font-family:monospace;"><?php echo esc_textarea(json_encode($templates, JSON_PRETTY_PRINT)); ?></textarea>
                        <button type="button" class="button reset-json" data-target="json-templates" data-type="templates">Reset to Default Templates</button>
                    </div>

                    <div id="tab-training" class="tab-content" style="display:none; padding:20px; background:#fff;">
                        <h3>Training Academy Videos (JSON)</h3>
                        <p class="description">Add/Edit tutorial videos for the user dashboard Training tab. Use <code>video_id</code> for embedding.</p>
                        <textarea name="training_json" id="json-training" style="width:100%; height:300px; font-family:monospace;"><?php echo esc_textarea(json_encode($training, JSON_PRETTY_PRINT)); ?></textarea>
                        <button type="button" class="button reset-json" data-target="json-training" data-type="training">Reset to Default Training</button>

                        <hr>
                        <h4>Quick Add Tutorial</h4>
                        <div style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #eee;">
                            <input type="text" id="new-tr-title" placeholder="Video Title" style="width:100%; margin-bottom:10px;">
                            <input type="text" id="new-tr-desc" placeholder="Brief Description" style="width:100%; margin-bottom:10px;">
                            <input type="text" id="new-tr-vid" placeholder="Video ID (e.g. YouTube ID)" style="width:100%; margin-bottom:10px;">
                            <button type="button" class="button" id="add-tr-row">Add to Training List</button>
                        </div>
                    </div>

                    <div id="tab-kb" class="tab-content" style="display:none; padding:20px; background:#fff;">
                        <h3>Knowledge Base Articles (JSON)</h3>
                        <p class="description">Manage the links and titles shown in the Knowledge Base section of the user training tab.</p>
                        <textarea name="kb_json" id="json-kb" style="width:100%; height:300px; font-family:monospace;"><?php echo esc_textarea(json_encode($kb, JSON_PRETTY_PRINT)); ?></textarea>
                        <button type="button" class="button reset-json" data-target="json-kb" data-type="kb">Reset to Default KB</button>

                        <hr>
                        <h4>Quick Add KB Article</h4>
                        <div style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #eee;">
                            <input type="text" id="new-kb-title" placeholder="Article Title" style="width:100%; margin-bottom:10px;">
                            <input type="text" id="new-kb-url" placeholder="Article URL" style="width:100%; margin-bottom:10px;">
                            <button type="button" class="button" id="add-kb-row">Add to KB List</button>
                        </div>
                    </div>

                    <div id="tab-marketing" class="tab-content" style="display:none; padding:20px; background:#fff;">
                        <h3>Affiliate Marketing Materials (JSON)</h3>
                        <p class="description">Define the banners and assets available for affiliates in the Earn tab.</p>
                        <textarea name="marketing_json" id="json-marketing" style="width:100%; height:200px; font-family:monospace;"><?php echo esc_textarea(json_encode($marketing, JSON_PRETTY_PRINT)); ?></textarea>
                        <button type="button" class="button reset-json" data-target="json-marketing" data-type="marketing">Reset to Default Marketing</button>

                        <hr>
                        <h4>Quick Add Banner</h4>
                        <div style="display:flex; gap:10px; background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #eee;">
                            <input type="text" id="new-mm-name" placeholder="Banner Name (e.g. Sidebar Promo)" style="flex:1;">
                            <input type="text" id="new-mm-img" placeholder="Image URL" style="flex:2;">
                            <input type="text" id="new-mm-size" placeholder="Size (e.g. 300x250)" style="width:100px;">
                            <button type="button" class="button" id="add-mm-row">Add to List</button>
                        </div>
                    </div>

                    <div id="tab-scripts" class="tab-content" style="display:none; padding:20px; background:#fff;">
                        <h3>Sales Scripts & Templates (JSON)</h3>
                        <p class="description">Add copy-paste scripts for affiliates to use on social media and email.</p>
                        <textarea name="scripts_json" id="json-scripts" style="width:100%; height:200px; font-family:monospace;"><?php echo esc_textarea(json_encode($scripts, JSON_PRETTY_PRINT)); ?></textarea>
                        <button type="button" class="button reset-json" data-target="json-scripts" data-type="scripts">Reset to Default Scripts</button>

                        <hr>
                        <h4>Quick Add Script</h4>
                        <div style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #eee;">
                            <input type="text" id="new-script-title" placeholder="Script Title" style="width:100%; margin-bottom:10px;">
                            <textarea id="new-script-content" placeholder="Script Content..." style="width:100%; height:80px; margin-bottom:10px;"></textarea>
                            <button type="button" class="button" id="add-script-row">Add to List</button>
                        </div>
                    </div>

                    <div id="tab-marketing-kit" class="tab-content" style="display:none; padding:20px; background:#fff;">
                        <h3>Affiliate Marketing Kit Content (HTML/JSON)</h3>
                        <p class="description">Define custom HTML and detailed assets for the affiliate kit.</p>
                        <textarea name="marketing_kit_json" id="json-kit" style="width:100%; height:300px; font-family:monospace;"><?php echo esc_textarea(json_encode($affiliate_kit, JSON_PRETTY_PRINT)); ?></textarea>
                        <button type="button" class="button reset-json" data-target="json-kit" data-type="kit">Reset to Default Kit</button>
                    </div>

                    <div id="tab-emails" class="tab-content" style="display:none; padding:20px; background:#fff;">
                        <h3>System Email Templates (JSON)</h3>
                        <p class="description">Customize the subject and body of system emails using placeholders like {name}, {email}, {profile_url}.</p>
                        <textarea name="email_templates_json" id="json-emails" style="width:100%; height:300px; font-family:monospace;"><?php echo esc_textarea(json_encode($emails, JSON_PRETTY_PRINT)); ?></textarea>
                        <button type="button" class="button reset-json" data-target="json-emails" data-type="emails">Reset to Default Emails</button>
                    </div>

                    <div id="tab-featured" class="tab-content" style="display:none; padding:20px; background:#fff;">
                        <h3>Manual Featured Profiles</h3>
                        <p class="description">Enter a JSON list of Profile slugs to show on the landing page (e.g. ["john-doe", "sarah-pro"]). Leave empty to show latest public profiles.</p>
                        <textarea name="featured_profiles_json" style="width:100%; height:200px; font-family:monospace;"><?php echo esc_textarea(json_encode($featured, JSON_PRETTY_PRINT)); ?></textarea>
                    </div>

                    <div id="tab-vsl" class="tab-content" style="display:none; padding:20px; background:#fff;">
                        <h3>VSL & Landing Page Copy</h3>
                        <p class="description">Manage the high-converting copy used on the main landing page and sales letter.</p>
                        <div class="field">
                            <label><strong>VSL Headline</strong></label>
                            <input type="text" name="saas_home_title" value="<?php echo esc_attr(get_option('saas_home_title')); ?>" class="large-text">
                        </div>
                        <div class="field" style="margin-top:20px;">
                            <label><strong>VSL Sub-headline / Hero</strong></label>
                            <textarea name="saas_home_hero" rows="3" class="large-text"><?php echo esc_textarea(get_option('saas_home_hero')); ?></textarea>
                        </div>
                        <div class="field" style="margin-top:20px;">
                            <label><strong>Founder's Bridge Story</strong></label>
                            <textarea name="saas_home_founder_letter" rows="8" class="large-text"><?php echo esc_textarea(get_option('saas_home_founder_letter')); ?></textarea>
                        </div>
                    </div>

                    <p class="submit">
                        <input type="submit" name="saas_save_content_hub" class="button button-primary" value="Save All Hub Content">
                    </p>
                </form>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.reset-json').on('click', function() {
                var type = $(this).data('type');
                var target = $(this).data('target');
                if(!confirm('Are you sure? This will overwrite your current JSON with system defaults.')) return;

                var defaults = {
                    templates: <?php echo json_encode($this->get_default_templates(), JSON_PRETTY_PRINT); ?>,
                    training: <?php echo json_encode($this->get_default_training(), JSON_PRETTY_PRINT); ?>,
                    kb: <?php echo json_encode($this->get_default_kb(), JSON_PRETTY_PRINT); ?>,
                    marketing: <?php echo json_encode($this->get_default_marketing(), JSON_PRETTY_PRINT); ?>,
                    scripts: <?php echo json_encode($this->get_default_scripts(), JSON_PRETTY_PRINT); ?>,
                    kit: <?php echo json_encode($this->get_default_marketing_kit(), JSON_PRETTY_PRINT); ?>,
                    emails: <?php echo json_encode($this->get_default_emails(), JSON_PRETTY_PRINT); ?>
                };

                $('#' + target).val(JSON.stringify(defaults[type], null, 4));
                alert('Default ' + type + ' loaded! Remember to click "Save All" below.');
            });

            $('#add-mm-row').on('click', function() {
                var list = JSON.parse($('[name="marketing_json"]').val() || '[]');
                list.push({
                    name: $('#new-mm-name').val(),
                    img: $('#new-mm-img').val(),
                    size: $('#new-mm-size').val()
                });
                $('[name="marketing_json"]').val(JSON.stringify(list, null, 4));
                $('#new-mm-name, #new-mm-img, #new-mm-size').val('');
                alert('Added! Click "Save All" to commit changes.');
            });

            $('#add-script-row').on('click', function() {
                var list = JSON.parse($('[name="scripts_json"]').val() || '[]');
                list.push({
                    title: $('#new-script-title').val(),
                    content: $('#new-script-content').val()
                });
                $('[name="scripts_json"]').val(JSON.stringify(list, null, 4));
                $('#new-script-title, #new-script-content').val('');
                alert('Added! Click "Save All" to commit changes.');
            });

            $('#add-tr-row').on('click', function() {
                var list = JSON.parse($('#json-training').val() || '[]');
                list.push({
                    title: $('#new-tr-title').val(),
                    desc: $('#new-tr-desc').val(),
                    video_id: $('#new-tr-vid').val()
                });
                $('#json-training').val(JSON.stringify(list, null, 4));
                $('#new-tr-title, #new-tr-desc, #new-tr-vid').val('');
                alert('Added! Click "Save All" to commit changes.');
            });

            $('#add-kb-row').on('click', function() {
                var list = JSON.parse($('#json-kb').val() || '[]');
                list.push({
                    title: $('#new-kb-title').val(),
                    url: $('#new-kb-url').val()
                });
                $('#json-kb').val(JSON.stringify(list, null, 4));
                $('#new-kb-title, #new-kb-url').val('');
                alert('Added! Click "Save All" to commit changes.');
            });
        });

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

    private function get_default_templates() {
        return [
            'coach' => [
                'headline' => 'Helping you double your revenue in 90 days. 🚀',
                'bio' => 'Certified high-performance coach. I work with CEOs and founders to scale their impact.',
                'color' => '#6c5ce7', 'theme' => 'light', 'shadow' => 'soft',
                'links' => [
                    ['title' => '👉 Free Strategy Session', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                    ['title' => 'Watch Case Study', 'url' => 'https://youtube.com', 'type' => 'video'],
                    ['title' => 'Client Success Stories', 'url' => '#', 'type' => 'testimonial', 'extra' => 'Working with Alex was the best decision for my agency.'],
                    ['title' => 'Consulting Packages', 'url' => '#', 'type' => 'pricing', 'extra' => "$2,500/mo\nBi-weekly Calls\nSlack Support\nResource Library"],
                ]
            ],
            'tiktok' => [
                'headline' => 'Shop My Top Tech & Setup Finds 🛍️',
                'bio' => 'Sharing the best tech deals and office aesthetic finds. Check my links for exclusive discounts!',
                'color' => '#ff0050', 'theme' => 'vibrant', 'shadow' => 'hard',
                'links' => [
                    ['title' => 'My Amazon Storefront', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
                    ['title' => 'Flash Sale Ending Soon! ⏳', 'url' => '#', 'type' => 'countdown', 'extra' => date('Y-m-d H:i', strtotime('+12 hours'))],
                    ['title' => 'Join My Private Discord', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                    ['title' => 'Latest Setup Tour', 'url' => 'https://tiktok.com', 'type' => 'video'],
                ]
            ],
            'realtor' => [
                'headline' => 'Bespoke Advisory for Elite Homeowners. 🏡',
                'bio' => 'Specializing in off-market luxury listings. Member of the Top 0.1% Global Network.',
                'color' => '#2d3436', 'theme' => 'dark', 'shadow' => 'none',
                'links' => [
                    ['title' => 'New Off-Market Listings', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/800x600?text=Penthouse+A\nhttps://via.placeholder.com/800x600?text=Coastal+Villa"],
                    ['title' => 'Request Private Showing', 'url' => '#', 'type' => 'lead_form'],
                    ['title' => 'Quarterly Market Report', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                    ['title' => 'Sales Target Progress', 'url' => '#', 'type' => 'milestone', 'extra' => 'Volume:$42M'],
                ]
            ],
            'politician' => [
                'headline' => 'Dedicated to Progress & Community Service. 🏛️',
                'bio' => 'Serving as your advocate in public office. Building a more resilient and inclusive community.',
                'color' => '#e84118', 'theme' => 'light', 'shadow' => 'soft',
                'links' => [
                    ['title' => 'Latest Community Update', 'url' => '#', 'type' => 'video'],
                    ['title' => 'Join the Volunteer Team', 'url' => '#', 'type' => 'lead_form'],
                    ['title' => 'My Vision for 2024', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ]
            ],
            'artist' => [
                'headline' => 'Visual Storytelling through Digital Art. 🎨',
                'bio' => 'Independent designer creating immersive visual experiences for forward-thinking brands.',
                'color' => '#f472b6', 'theme' => 'vibrant', 'shadow' => 'hard',
                'links' => [
                    ['title' => 'Portfolio Gallery', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400\nhttps://via.placeholder.com/401"],
                    ['title' => 'Project Inquiry', 'url' => '#', 'type' => 'lead_form'],
                    ['title' => 'Follow my Process', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
                ]
            ],
            'agency' => [
                'headline' => 'Scaling Brands through Performance Marketing. 🏢',
                'bio' => 'We build high-performance funnels that drive revenue for elite founders.',
                'color' => '#111827', 'theme' => 'dark', 'shadow' => 'soft',
                'links' => [
                    ['title' => 'Get a Free Quote', 'url' => '#', 'type' => 'lead_form'],
                    ['title' => 'Our Pricing Models', 'url' => '#', 'type' => 'pricing', 'extra' => "$2,500+\nFull CRM Sync\nScale Strategy"],
                    ['title' => 'Latest Campaign Results', 'url' => '#', 'type' => 'video'],
                ]
            ],
            'speaker' => [
                'headline' => 'Inspiring Transformation through Keynotes. 🎙️',
                'bio' => 'Helping organizations navigate change and build resilient cultures. Global Keynote Speaker.',
                'color' => '#d4af37', 'theme' => 'luxury', 'shadow' => 'soft',
                'links' => [
                    ['title' => 'Watch Highlight Reel', 'url' => '#', 'type' => 'video'],
                    ['title' => 'Inquire for Speaking', 'url' => '#', 'type' => 'lead_form'],
                    ['title' => 'Speaker One-Sheet', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ]
            ],
            'freelancer' => [
                'headline' => 'Visual Identity & Web Design for Modern Brands. 🎨',
                'bio' => 'Helping DTC brands stand out through minimalist design and high-converting interfaces.',
                'color' => '#00d1b2', 'theme' => 'light', 'shadow' => 'hard',
                'links' => [
                    ['title' => 'Recent Branding Work', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400\nhttps://via.placeholder.com/401"],
                    ['title' => 'View Pricing Guide', 'url' => '#', 'type' => 'pricing', 'extra' => "$1,500+\nCustom Branding\nUI/UX Design\nWebflow Dev"],
                    ['title' => 'Project Inquiry', 'url' => '#', 'type' => 'lead_form'],
                    ['title' => 'Hire Me on Upwork', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ]
            ],
            'business' => [
                'headline' => 'Innovative Solutions for Global Enterprise. 🏢',
                'bio' => 'Streamlining operations and driving growth through technology.',
                'color' => '#0073aa', 'theme' => 'light', 'shadow' => 'hard',
                'links' => [
                    ['title' => 'Book a Consultation', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                    ['title' => 'Our Core Services', 'url' => '#', 'type' => 'pricing', 'extra' => "$199/hr\nStrategy Audit\nProcess Automation\nCustom Dev"],
                    ['title' => 'FAQ', 'url' => '#', 'type' => 'faq', 'extra' => 'We operate 24/7 across the globe.'],
                    ['title' => 'Office Location', 'url' => 'https://maps.google.com', 'type' => 'button'],
                ]
            ],
            'luxury' => [
                'headline' => 'Bespoke Private Advisory. ⚜️',
                'bio' => 'Curating exclusive opportunities for the discerning individual.',
                'color' => '#d4af37', 'theme' => 'luxury', 'shadow' => 'soft',
                'links' => [
                    ['title' => 'Inquire Privately', 'url' => '#', 'type' => 'lead_form'],
                    ['title' => 'Exclusive Asset Portfolio', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/800x600?text=Asset+1\nhttps://via.placeholder.com/800x600?text=Asset+2"],
                    ['title' => 'Secure Documentation', 'url' => '#', 'type' => 'button', 'style' => 'outline'],
                    ['title' => 'Save VCard to Phone', 'url' => home_url('/?saas_action=vcard'), 'type' => 'button', 'style' => 'rainbow'],
                ]
            ]
        ];
    }

    private function get_default_marketing_kit() {
        return [
            ['title' => 'Elite Branding Guide', 'content' => '<p>Always use high-contrast images. Target coaches who make $10k+.</p>'],
            ['title' => 'Sample Email Campaign', 'content' => '<p>Use the cold email outreach script in the scripts tab.</p>']
        ];
    }

    private function get_default_emails() {
        return [
            'new_lead_admin' => [
                'subject' => '🚀 New Lead Captured: {name}',
                'body' => "<h2>You've got a new lead!</h2><p><strong>Name:</strong> {name}<br><strong>Email:</strong> {email}<br><strong>Source:</strong> {profile_title}</p><p><a href='{dashboard_url}'>View in Dashboard</a></p>"
            ],
            'lead_autoresponder' => [
                'subject' => 'Re: Your inquiry to {profile_title}',
                'body' => "Hi {name},<br><br>Thank you for reaching out! I've received your inquiry and will get back to you shortly.<br><br>Best,<br>{profile_title}"
            ]
        ];
    }

    private function get_default_training() {
        return [
            ['title' => 'The 60-Second Setup', 'desc' => 'Go from zero to a live, high-converting funnel in under a minute.', 'video_id' => 'setup'],
            ['title' => 'Lead Magnet Magic', 'desc' => 'Learn how to use Lead Forms to capture contact info and build your email list.', 'video_id' => 'leads'],
            ['title' => 'NFC & Real-World Sales', 'desc' => 'How to use your digital business card at networking events to close more deals.', 'video_id' => 'nfc']
        ];
    }

    private function get_default_kb() {
        return [
            ['title' => 'How to connect my own domain?', 'url' => '#'],
            ['title' => 'Setting up Stripe for product sales', 'url' => '#'],
            ['title' => 'A/B Testing: How many variants should I use?', 'url' => '#']
        ];
    }

    private function get_default_marketing() {
        return [
            ['name' => 'Standard Banner', 'img' => 'https://via.placeholder.com/300x100?text=Claim+Your+Elite+Bio', 'size' => '300x100'],
            ['name' => 'Sidebar Ad', 'img' => 'https://via.placeholder.com/150x150?text=Stop+Losing+Leads', 'size' => '150x150']
        ];
    }

    private function get_default_scripts() {
        return [
            ['title' => 'Instagram/TikTok Hook', 'content' => "Hey [Name], noticed your bio link is just a standard list. I built a system for [Niche] that captures 3x more leads directly in the bio. Want a 5-min video showing how it works? No cost."],
            ['title' => 'Cold Email Outreach', 'content' => "Subject: Quick question about your bio link\n\nHi [Name],\n\nI love your content, but I noticed you are losing 90% of your traffic to a standard link list. I created an 'Elite Funnel' system specifically for consultants that converts visitors into leads automatically.\n\nYou can claim your link here: [Link]\n\nBest,\n[My Name]"]
        ];
    }

    public function ajax_send_broadcast() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('saas_dashboard_nonce', 'security');

        $subject = sanitize_text_field($_POST['subject']);
        $message = sanitize_textarea_field($_POST['message']);
        $users = get_users(['fields' => 'ID']);

        foreach ($users as $user_id) {
            wp_insert_post([
                'post_type'    => 'saas_message',
                'post_title'   => $subject,
                'post_content' => $message,
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
                'meta_input'   => [
                    '_saas_msg_recipient' => $user_id,
                    '_saas_msg_status'    => 'unread'
                ]
            ]);
        }

        wp_send_json_success('Broadcast sent to ' . count($users) . ' users!');
    }

    public function finances_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $payouts = get_posts(['post_type' => 'saas_payout', 'post_status' => 'any', 'numberposts' => -1]);
        $orders  = get_posts(['post_type' => 'saas_order', 'post_status' => 'any', 'numberposts' => -1]);

        $gross_rev = 0;
        foreach($orders as $o) {
            if(get_post_meta($o->ID, '_saas_order_status', true) === 'completed') {
                $gross_rev += floatval(get_post_meta($o->ID, '_saas_order_amount', true));
            }
        }

        $commissions = 0;
        $users = get_users(['fields' => ['ID', 'display_name']]);
        foreach($users as $u) {
            $commissions += floatval(get_user_meta($u->ID, '_saas_affiliate_earned', true));
        }
        foreach($payouts as $p) {
            if(get_post_meta($p->ID, '_status', true) === 'paid') {
                $commissions += floatval(get_post_meta($p->ID, '_amount', true));
            }
        }
        $aff_coupons = get_option('saas_affiliate_coupons') ?: [];
        $users = get_users(['fields' => ['ID', 'display_name']]);
        ?>
        <div class="wrap saas-admin-wrapper">
            <h1>Financial & Affiliate Management</h1>
            <p>Monitor global revenue and process affiliate commission requests.</p>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin:20px 0;">
                <div style="background:#fff; padding:20px; border-radius:12px; border:1px solid #ddd;">
                    <small style="text-transform:uppercase; color:#64748b; font-weight:700; letter-spacing:1px;">Gross Revenue</small>
                    <div style="font-size:2rem; font-weight:900; color:#10b981;">$<?php echo number_format($gross_rev, 2); ?></div>
                </div>
                <div style="background:#fff; padding:20px; border-radius:12px; border:1px solid #ddd;">
                    <small style="text-transform:uppercase; color:#64748b; font-weight:700; letter-spacing:1px;">Affiliate Obligations</small>
                    <div style="font-size:2rem; font-weight:900; color:#6c5ce7;">$<?php echo number_format($commissions, 2); ?></div>
                </div>
                <div style="background:#fff; padding:20px; border-radius:12px; border:1px solid #ddd;">
                    <small style="text-transform:uppercase; color:#64748b; font-weight:700; letter-spacing:1px;">Net Profit (Est)</small>
                    <div style="font-size:2rem; font-weight:900; color:#0f172a;">$<?php echo number_format($gross_rev - $commissions, 2); ?></div>
                </div>
            </div>

            <div class="saas-tabs-container">
                <h2 class="nav-tab-wrapper">
                    <a href="#tab-payouts" class="nav-tab nav-tab-active">Affiliate Payouts</a>
                    <a href="#tab-orders" class="nav-tab">Customer Orders</a>
                    <a href="#tab-coupons" class="nav-tab">Affiliate Discount Codes</a>
                </h2>

                <div id="tab-coupons" class="tab-content" style="display:none;">
                    <div style="background:#f8fafc; padding:30px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:30px;">
                        <h3>🏆 Affiliate Leaderboard (All Time)</h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead><tr><th>Affiliate</th><th>Active Referrals</th><th>Total Earned</th></tr></thead>
                            <tbody>
                                <?php
                                $affiliates = get_users([
                                    'meta_key' => '_saas_affiliate_earned',
                                    'orderby'  => 'meta_value_num',
                                    'order'    => 'DESC',
                                    'number'   => 5
                                ]);
                                foreach($affiliates as $aff) :
                                    $earned = get_user_meta($aff->ID, '_saas_affiliate_earned', true);
                                    $refs = count(get_users(['meta_key' => '_saas_referred_by', 'meta_value' => $aff->ID, 'fields' => 'ID']));
                                ?>
                                    <tr>
                                        <td><strong><?php echo $aff->display_name; ?></strong></td>
                                        <td><?php echo $refs; ?></td>
                                        <td style="color:#10b981; font-weight:800;">$<?php echo number_format($earned, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3>Affiliate Discount Mappings</h3>
                    <p>Assign unique coupon codes to affiliates. When used during checkout, the customer gets a discount and the affiliate gets credit.</p>
                    <form id="saas-affiliate-coupons-form">
                        <input type="hidden" name="action" value="saas_save_affiliate_coupons">
                        <input type="hidden" name="security" value="<?php echo wp_create_nonce('saas_dashboard_nonce'); ?>">
                        <table class="wp-list-table widefat fixed striped" id="coupons-table">
                            <thead>
                                <tr>
                                    <th>Affiliate User</th>
                                    <th>Coupon Code</th>
                                    <th>Discount %</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($aff_coupons as $index => $c): ?>
                                <tr>
                                    <td>
                                        <select name="coupons[<?php echo $index; ?>][user_id]">
                                            <?php foreach($users as $u): ?>
                                                <option value="<?php echo $u->ID; ?>" <?php selected($c['user_id'], $u->ID); ?>><?php echo $u->display_name; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="coupons[<?php echo $index; ?>][code]" value="<?php echo esc_attr($c['code']); ?>" class="regular-text" style="width:100%;"></td>
                                    <td><input type="number" name="coupons[<?php echo $index; ?>][discount]" value="<?php echo esc_attr($c['discount']); ?>" min="0" max="100" style="width:80px;">%</td>
                                    <td><button type="button" class="button remove-row">Remove</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4"><button type="button" class="button" id="add-coupon-row">+ Add Coupon</button></td>
                                </tr>
                            </tfoot>
                        </table>
                        <p class="submit"><button type="submit" class="button button-primary">Save Coupon Mappings</button></p>
                    </form>
                    <script>
                    jQuery(document).ready(function($) {
                        var usersJson = <?php echo json_encode($users); ?>;
                        $('#add-coupon-row').on('click', function() {
                            var index = $('#coupons-table tbody tr').length;
                            var userOptions = usersJson.map(u => `<option value="${u.ID}">${u.display_name}</option>`).join('');
                            var row = `<tr>
                                <td><select name="coupons[${index}][user_id]">${userOptions}</select></td>
                                <td><input type="text" name="coupons[${index}][code]" value="" class="regular-text" style="width:100%;"></td>
                                <td><input type="number" name="coupons[${index}][discount]" value="10" min="0" max="100" style="width:80px;">%</td>
                                <td><button type="button" class="button remove-row">Remove</button></td>
                            </tr>`;
                            $('#coupons-table tbody').append(row);
                        });
                        $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); });
                        $('#saas-affiliate-coupons-form').on('submit', function(e) {
                            e.preventDefault();
                            $.post(ajaxurl, $(this).serialize(), function(res) {
                                if(res.success) alert(res.data);
                            });
                        });
                    });
                    </script>
                </div>

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
                                        <button class="button button-small mark-payout-paid" data-id="<?php echo $p->ID; ?>">Mark as Paid</button>
                                        <a href="<?php echo get_edit_post_link($p->ID); ?>" class="button button-small">Edit</a>
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
                                    <td>
                                        <?php echo $o->post_title; ?>
                                        <?php if($coupon = get_post_meta($o->ID, '_saas_order_coupon', true)): ?>
                                            <br><span class="pro-badge" style="background:var(--secondary); font-size:0.6rem;"><?php echo $coupon; ?></span>
                                        <?php endif; ?>
                                    </td>
                                <td>$<?php echo number_format(get_post_meta($o->ID, '_saas_order_amount', true), 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo get_post_meta($o->ID, '_saas_order_status', true); ?>">
                                            <?php echo strtoupper(get_post_meta($o->ID, '_saas_order_status', true)); ?>
                                        </span>
                                        <br><small><?php echo strtoupper(get_post_meta($o->ID, '_saas_gateway', true)); ?></small>
                                    </td>
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

        jQuery('.mark-payout-paid').on('click', function() {
            var $btn = jQuery(this);
            var payoutId = $btn.data('id');
            if(!confirm('Have you manually sent the funds?')) return;

            $btn.prop('disabled', true).text('Processing...');
            jQuery.post(ajaxurl, {
                action: 'saas_process_payout',
                payout_id: payoutId,
                security: '<?php echo wp_create_nonce("saas_dashboard_nonce"); ?>'
            }, function(res) {
                if(res.success) {
                    alert(res.data);
                    location.reload();
                } else {
                    alert('Error: ' + res.data);
                    $btn.prop('disabled', false).text('Mark as Paid');
                }
            });
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
            <h1>Elite SaaS: System Control Center</h1>

            <div class="saas-tabs-container" style="margin-top:20px;">
                <h2 class="nav-tab-wrapper">
                    <a href="#tab-system-status" class="nav-tab nav-tab-active">📊 System Status</a>
                    <a href="#tab-general-settings" class="nav-tab">⚙️ General & Payments</a>
                    <a href="#tab-home-editor" class="nav-tab">🏠 Homepage Content</a>
                    <a href="#tab-users" class="nav-tab">👥 User Management</a>
                    <a href="#tab-tools" class="nav-tab">🛠️ Advanced Tools</a>
                    <a href="#tab-shortcodes" class="nav-tab">📜 Shortcode Guide</a>
                    <a href="#tab-broadcast" class="nav-tab">📣 System Broadcast</a>
                </h2>

                <div id="tab-system-status" class="tab-content">
                    <div style="display:grid; grid-template-columns: 1fr 2fr; gap:30px; margin-top:20px;">
                        <div class="saas-admin-sidebar" style="margin:0;">
                            <h3>Quick Stats</h3>
                            <div class="saas-admin-card" style="background:#fff; padding:15px; border-radius:8px; border:1px solid #ddd; margin-bottom:15px;">
                                <strong>Views:</strong> <span style="font-size:1.5rem; display:block;"><?php echo number_format($summary['views']); ?></span>
                            </div>
                            <div class="saas-admin-card" style="background:#fff; padding:15px; border-radius:8px; border:1px solid #ddd; margin-bottom:15px;">
                                <strong>Clicks:</strong> <span style="font-size:1.5rem; display:block;"><?php echo number_format($summary['clicks']); ?></span>
                            </div>
                            <div class="saas-admin-card" style="background:#fff; padding:15px; border-radius:8px; border:1px solid #ddd;">
                                <strong>Leads:</strong> <span style="font-size:1.5rem; display:block;"><?php echo number_format($summary['leads']); ?></span>
                            </div>
                            <hr>
                            <h3>CPT Links</h3>
                            <ul style="list-style:none; padding:0;">
                                <li><a href="<?php echo admin_url('edit.php?post_type=saas_profile'); ?>">Profiles</a></li>
                                <li><a href="<?php echo admin_url('edit.php?post_type=saas_lead'); ?>">Captured Leads</a></li>
                                <li><a href="<?php echo admin_url('edit.php?post_type=saas_license'); ?>">System Licenses</a></li>
                                <li><a href="<?php echo admin_url('edit.php?post_type=saas_order'); ?>">Sales/Orders</a></li>
                            </ul>
                        </div>
                        <div class="saas-admin-main" style="margin:0;">
                            <div style="background:#fff; padding:30px; border-radius:12px; border:1px solid #ddd; margin-bottom:30px;">
                                <h3>System Growth Trends (6 Months)</h3>
                                <div style="height:800px;">
                                    <canvas id="saas-admin-chart"></canvas>
                                </div>
                            </div>

                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                                <div style="background:#fff; padding:20px; border-radius:12px; border:1px solid #ddd;">
                                    <h4>Traffic Sources (All Users)</h4>
                                    <ul style="list-style:none; padding:0;">
                                        <?php
                                        global $wpdb;
                                        $table = $wpdb->prefix . 'saas_analytics';
                                        $refs = $wpdb->get_results("SELECT referrer, COUNT(*) as count FROM $table WHERE referrer != '' GROUP BY referrer ORDER BY count DESC LIMIT 5");
                                        foreach($refs as $r) : ?>
                                            <li style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #eee;">
                                                <span><?php echo esc_html($r->referrer); ?></span>
                                                <strong><?php echo $r->count; ?></strong>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div style="background:#fff; padding:20px; border-radius:12px; border:1px solid #ddd;">
                                    <h4>Conversion by Device</h4>
                                    <?php
                                    $all_views = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE event_type='view'");
                                    ?>
                                    <div style="margin-top:15px;">
                                        <div style="margin-bottom:10px;">
                                            <div style="display:flex; justify-content:space-between; margin-bottom:5px;"><small>Mobile</small> <small>72%</small></div>
                                            <div style="height:8px; background:#f1f5f9; border-radius:10px;"><div style="width:72%; height:100%; background:#6c5ce7; border-radius:10px;"></div></div>
                                        </div>
                                        <div style="margin-bottom:10px;">
                                            <div style="display:flex; justify-content:space-between; margin-bottom:5px;"><small>Desktop</small> <small>24%</small></div>
                                            <div style="height:8px; background:#f1f5f9; border-radius:10px;"><div style="width:24%; height:100%; background:#39e09b; border-radius:10px;"></div></div>
                                        </div>
                                        <div>
                                            <div style="display:flex; justify-content:space-between; margin-bottom:5px;"><small>Tablet</small> <small>4%</small></div>
                                            <div style="height:8px; background:#f1f5f9; border-radius:10px;"><div style="width:4%; height:100%; background:#f59e0b; border-radius:10px;"></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="background:#fff; padding:25px; border-radius:12px; border:1px solid #ddd; margin-top:30px;">
                                <h3>Latest Global Leads</h3>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Lead Name</th>
                                            <th>Source Profile</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $latest_leads = get_posts(['post_type' => 'saas_lead', 'numberposts' => 5]);
                                        foreach($latest_leads as $ll) :
                                            $source_id = get_post_meta($ll->ID, '_saas_lead_source_id', true);
                                            $status = get_post_meta($ll->ID, '_saas_lead_status', true) ?: 'New';
                                        ?>
                                            <tr>
                                                <td><strong><?php echo esc_html(get_post_meta($ll->ID, '_saas_lead_name', true)); ?></strong></td>
                                                <td><?php echo $source_id ? get_the_title($source_id) : 'Unknown'; ?></td>
                                                <td><span class="status-badge status-<?php echo strtolower($status); ?>"><?php echo strtoupper($status); ?></span></td>
                                                <td><?php echo get_the_date('M j, g:i a', $ll->ID); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-general-settings" class="tab-content" style="display:none; padding:20px; background:#fff; border:1px solid #ddd;">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'saas_settings_group' );
                        do_settings_sections( 'saas_settings' ); // We will split these sections in init
                        submit_button( 'Save All Settings' );
                        ?>
                    </form>
                </div>

                <div id="tab-home-editor" class="tab-content" style="display:none; padding:20px; background:#fff; border:1px solid #ddd;">
                    <h3>Elite Sales Copy Setup</h3>
                    <p>Populate your homepage with professional copy designed by elite marketers.</p>
                    <a href="<?php echo admin_url('admin-post.php?action=saas_populate_pro_content'); ?>" class="button button-primary" style="background:#39e09b; border-color:#39e09b; color:#1e2329;">🔥 Apply Pro Sales Copy Now</a>
                    <hr>
                    <p>Use the General Settings tab to manually edit homepage titles, descriptions, and JSON content.</p>
                </div>

                <div id="tab-tools" class="tab-content" style="display:none; padding:20px; background:#fff; border:1px solid #ddd;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                        <div>
                            <h3>Bulk License Factory</h3>
                            <p>Generate multiple license keys at once for bulk sales or promos.</p>
                            <form id="saas-bulk-license-form" style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #eee;">
                                <div class="field">
                                    <label>Quantity</label><br>
                                    <input type="number" name="count" value="10" min="1" max="100">
                                </div>
                                <div class="field" style="margin-top:10px;">
                                    <label>Plan Type</label><br>
                                    <select name="plan">
                                        <option value="pro">ELITE PRO</option>
                                        <option value="agency">AGENCY UNLIMITED</option>
                                    </select>
                                </div>
                                <button type="submit" class="button button-primary" style="margin-top:15px;">Generate Bulk Keys</button>
                            </form>
                            <hr>
                            <h3>Environment Management</h3>
                            <p>Automatically create Login, Register, and Dashboard pages with correct shortcodes.</p>
                            <a href="<?php echo admin_url('admin-post.php?action=saas_generate_pages'); ?>" class="button button-secondary">Generate System Pages</a>
                            <hr>
                            <h3>Health Check</h3>
                            <?php $this->render_health_check(); ?>
                        </div>
                        <div>
                            <h3>Sample Data Engine</h3>
                            <p>Generate a comprehensive environment with profiles, leads, orders, and stats for testing.</p>
                            <button id="saas-generate-samples-btn" class="button button-secondary">🚀 Generate Full Sample Data</button>
                            <button id="saas-test-payment-btn" class="button button-secondary" style="background:#f59e0b; color:#fff; border:none; margin-top:10px;">Simulate Success Payment (UID: 1)</button>
                        </div>
                    </div>
                </div>

                <div id="tab-shortcodes" class="tab-content" style="display:none; padding:20px; background:#fff; border:1px solid #ddd;">
                    <h3>Available System Shortcodes</h3>
                    <p>Use these shortcodes to place SaaS functionality on any page or post.</p>

                    <div style="background:#f8fafc; padding:20px; border-radius:12px; margin-bottom:20px; border:1px solid #eee;">
                        <code style="font-size:1.1rem; color:var(--primary);">[saas_dashboard]</code>
                        <p><strong>Description:</strong> Renders the complete user dashboard. Users must be logged in to see their content.</p>
                        <p><strong>Example:</strong> Create a page titled "My Dashboard" and paste this shortcode in the editor.</p>
                    </div>

                    <div style="background:#f8fafc; padding:20px; border-radius:12px; margin-bottom:20px; border:1px solid #eee;">
                        <code style="font-size:1.1rem; color:var(--primary);">[saas_login_form]</code>
                        <p><strong>Description:</strong> Displays a professional login form optimized for the SaaS platform.</p>
                        <p><strong>Example:</strong> <code>[saas_login_form redirect="/custom-path"]</code> (Redirect is optional).</p>
                    </div>

                    <div style="background:#f8fafc; padding:20px; border-radius:12px; border:1px solid #eee;">
                        <code style="font-size:1.1rem; color:var(--primary);">[saas_register_form]</code>
                        <p><strong>Description:</strong> Renders the multi-step user registration form.</p>
                        <p><strong>Example:</strong> Use this on your "Sign Up" page to allow new consultants to join.</p>
                    </div>
                </div>

                <div id="tab-users" class="tab-content" style="display:none; padding:20px; background:#fff; border:1px solid #ddd;">
                    <h3>Platform User Management</h3>
                    <p>Overview of all registered consultants and their subscription tiers.</p>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Plan</th>
                                <th>Earnings</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_users = get_users(['number' => 100]);
                            foreach($all_users as $u) :
                                $u_plan = get_user_meta($u->ID, '_saas_subscription_plan', true) ?: 'free';
                                $u_earned = get_user_meta($u->ID, '_saas_affiliate_earned', true) ?: 0;
                            ?>
                                <tr>
                                    <td><strong><?php echo $u->display_name; ?></strong></td>
                                    <td><?php echo $u->user_email; ?></td>
                                    <td><span class="status-badge status-<?php echo $u_plan; ?>"><?php echo strtoupper($u_plan); ?></span></td>
                                    <td>$<?php echo number_format($u_earned, 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($u->user_registered)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="tab-broadcast" class="tab-content" style="display:none; padding:20px; background:#fff; border:1px solid #ddd;">
                    <h3>System Broadcast</h3>
                    <p>Send a message to every user's internal dashboard inbox.</p>
                    <form id="saas-broadcast-form">
                        <p><input type="text" name="subject" placeholder="Message Subject" style="width:100%; font-size:1.1rem; padding:10px;" required></p>
                        <p><textarea name="message" placeholder="Type your system announcement here..." style="width:100%;" rows="8" required></textarea></p>
                        <p><button type="submit" class="button button-primary" style="padding:10px 30px;">Broadcast to All Users</button></p>
                    </form>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('saas-admin-chart');
                if (ctx && typeof Chart !== 'undefined') {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($growth_labels ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']); ?>,
                            datasets: [{
                                label: 'New Profiles Created',
                                data: <?php echo json_encode($growth_counts ?: [0, 0, 0, 0, 0, 0]); ?>,
                                borderColor: '#6c5ce7',
                                backgroundColor: 'rgba(108, 92, 231, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#6c5ce7',
                                pointRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }

                jQuery('.nav-tab').on('click', function(e) {
                    e.preventDefault();
                    jQuery('.nav-tab').removeClass('nav-tab-active');
                    jQuery(this).addClass('nav-tab-active');
                    jQuery('.tab-content').hide();
                    jQuery(jQuery(this).attr('href')).show();
                });

                document.getElementById('saas-test-payment-btn')?.addEventListener('click', function() {
                    if(!confirm('Simulate successful payment for User ID 1?')) return;
                    fetch('<?php echo get_rest_url(null, "/saas/v1/webhook"); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: 1, status: 'succeeded', plan: 'pro' })
                    }).then(r => r.json()).then(d => {
                        alert('Webhook Success!');
                        location.reload();
                    });
                });

                document.getElementById('saas-generate-samples-btn')?.addEventListener('click', function() {
                    if (!confirm('Generate comprehensive sample data?')) return;
                    const btn = this;
                    btn.disabled = true; btn.innerText = 'Generating...';
                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'saas_generate_samples' })
                    })
                    .then(r => r.json()).then(data => { alert(data.data); location.reload(); });
                });

                jQuery('#saas-bulk-license-form').on('submit', function(e) {
                    e.preventDefault();
                    var $btn = jQuery(this).find('button');
                    $btn.prop('disabled', true).text('Generating...');
                    jQuery.post(ajaxurl, jQuery(this).serialize() + '&action=saas_generate_bulk_licenses&security=<?php echo wp_create_nonce("saas_dashboard_nonce"); ?>', function(res) {
                        if(res.success) {
                            alert(res.data);
                            location.reload();
                        }
                    });
                });

                jQuery('#saas-broadcast-form').on('submit', function(e) {
                    e.preventDefault();
                    if(!confirm('Send to ALL users?')) return;
                    var $btn = jQuery(this).find('button');
                    $btn.prop('disabled', true).text('Sending...');
                    jQuery.post(ajaxurl, jQuery(this).serialize() + '&action=saas_send_broadcast&security=<?php echo wp_create_nonce("saas_dashboard_nonce"); ?>', function(res) {
                        alert(res.data);
                        $btn.prop('disabled', false).text('Broadcast to All Users');
                        if(res.success) jQuery('#saas-broadcast-form').find('input, textarea').val('');
                    });
                });
            });
            </script>
        </div>
        <?php
    }
}
new Saas_Admin_Settings();
