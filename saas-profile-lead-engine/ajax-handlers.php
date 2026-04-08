<?php
/**
 * Dashboard AJAX Handlers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. AJAX: Update Link Order
add_action( 'wp_ajax_saas_update_link_order', 'saas_ajax_update_link_order' );
function saas_ajax_update_link_order() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $link_ids = isset( $_POST['link_ids'] ) ? (array) $_POST['link_ids'] : [];

    if ( empty( $link_ids ) ) {
        wp_send_json_error( 'Invalid link IDs' );
    }

    foreach ( $link_ids as $index => $id ) {
        // Ensure user owns the link
        $post = get_post( $id );
        if ( $post && $post->post_author == get_current_user_id() ) {
            update_post_meta( $id, '_saas_priority', $index );
        }
    }

    wp_send_json_success( 'Order updated' );
}

// 2. AJAX: Add New Link
add_action( 'wp_ajax_saas_add_link', 'saas_ajax_add_link' );
function saas_ajax_add_link() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $title = sanitize_text_field( $_POST['title'] );
    $url   = esc_url_raw( $_POST['url'] );
    $type  = sanitize_text_field( $_POST['block_type'] );
    $style = sanitize_text_field( $_POST['block_style'] );
    $animation = sanitize_text_field( $_POST['block_animation'] );

    if ( empty( $title ) || empty( $url ) ) {
        wp_send_json_error( 'Missing fields' );
    }

    $link_id = wp_insert_post([
        'post_type'   => 'saas_link',
        'post_title'  => $title,
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]);

    if ( ! is_wp_error( $link_id ) ) {
        update_post_meta( $link_id, '_saas_block_type', $type );
        update_post_meta( $link_id, '_saas_block_style', $style );
        update_post_meta( $link_id, '_saas_block_animation', $animation );
        update_post_meta( $link_id, '_saas_link_url', $url );
        update_post_meta( $link_id, '_saas_priority', 0 );

        // Extended meta for complex blocks
        if ($type === 'testimonial' && isset($_POST['extra'])) {
            update_post_meta($link_id, '_saas_testimonial_text', sanitize_textarea_field($_POST['extra']));
        } elseif ($type === 'faq' && isset($_POST['extra'])) {
            update_post_meta($link_id, '_saas_faq_answer', sanitize_textarea_field($_POST['extra']));
        } elseif (($type === 'pricing' || $type === 'product') && isset($_POST['extra'])) {
            update_post_meta($link_id, '_saas_price', sanitize_text_field($_POST['extra']));
            if ($type === 'pricing') {
                update_post_meta($link_id, '_saas_features', ['Premium Support', 'Unlimited Links', 'No Branding']);
            }
        } elseif ($type === 'image_gallery' && isset($_POST['extra'])) {
            $urls = array_filter(array_map('trim', explode("\n", $_POST['extra'])));
            update_post_meta($link_id, '_saas_gallery_images', $urls);
        } elseif ($type === 'social_icons' && isset($_POST['extra'])) {
            // extra: platform:url newline separated
            $lines = array_filter(array_map('trim', explode("\n", $_POST['extra'])));
            $data = [];
            foreach ($lines as $l) {
                if (strpos($l, ':') !== false) {
                    list($p, $u) = explode(':', $l, 2);
                    $data[trim($p)] = trim($u);
                }
            }
            update_post_meta($link_id, '_saas_social_data', $data);
        } elseif ($type === 'countdown' && isset($_POST['extra'])) {
            update_post_meta($link_id, '_saas_expiry', sanitize_text_field($_POST['extra']));
        } elseif ($type === 'milestone' && isset($_POST['extra'])) {
            if (strpos($_POST['extra'], ':') !== false) {
                list($lbl, $per) = explode(':', $_POST['extra'], 2);
                update_post_meta($link_id, '_saas_ms_label', sanitize_text_field($lbl));
                update_post_meta($link_id, '_saas_ms_percent', intval($per));
            }
        }

        wp_send_json_success([ 'id' => $link_id, 'title' => $title, 'url' => $url, 'type' => $type, 'style' => $style ]);
    } else {
        wp_send_json_error( 'Failed to add link' );
    }
}

// 3. AJAX: Save Profile
add_action( 'wp_ajax_saas_save_profile', 'saas_ajax_save_profile' );
function saas_ajax_save_profile() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $user_id = get_current_user_id();
    $profile_id = $_POST['profile_id']; // Should verify ownership

    // Verify ownership
    $profile = get_post( $profile_id );
    if ( ! $profile || $profile->post_author != $user_id ) {
        wp_send_json_error( 'Unauthorized' );
    }

    $data = [
        'bio'         => $_POST['bio'] ?? '',
        'headline'    => $_POST['headline'] ?? '',
        'theme_color' => $_POST['theme_color'] ?? '',
    ];

    saas_update_profile_meta( $profile_id, $data );

    // Profile specific
    if (isset($_POST['phone'])) {
        update_post_meta($profile_id, '_saas_phone', sanitize_text_field($_POST['phone']));
    }
    if (isset($_POST['profile_image_id'])) {
        set_post_thumbnail($profile_id, intval($_POST['profile_image_id']));
    }
    if (isset($_POST['cover_image_id'])) {
        update_post_meta($profile_id, '_saas_cover_id', intval($_POST['cover_image_id']));
    }

    // Branding specific
    if (isset($_POST['bg_type'])) {
        update_post_meta($profile_id, '_saas_bg_type', sanitize_text_field( $_POST['bg_type'] ) );
        update_post_meta($profile_id, '_saas_bg_color', sanitize_text_field( $_POST['bg_value'] ) );
        if ($_POST['bg_type'] === 'gradient') {
            update_post_meta($profile_id, '_saas_bg_gradient', sanitize_text_field( $_POST['bg_value'] ) );
        }
    }
    if (isset($_POST['btn_shape'])) {
        update_post_meta($profile_id, '_saas_btn_shape', sanitize_text_field($_POST['btn_shape']));
    }
    if (isset($_POST['social_proof'])) {
        update_post_meta($profile_id, '_saas_social_proof', 1);
    } else {
        update_post_meta($profile_id, '_saas_social_proof', 0);
    }
    if (isset($_POST['hide_branding'])) {
        update_post_meta($profile_id, '_saas_hide_branding', 1);
    } else {
        update_post_meta($profile_id, '_saas_hide_branding', 0);
    }
    if (isset($_POST['font_family'])) {
        update_post_meta($profile_id, '_saas_font_family', sanitize_text_field($_POST['font_family']));
    }
    if (isset($_POST['container_shadow'])) {
        update_post_meta($profile_id, '_saas_container_shadow', sanitize_text_field($_POST['container_shadow']));
    }
    if (isset($_POST['profile_theme'])) {
        update_post_meta($profile_id, '_saas_profile_theme', sanitize_text_field($_POST['profile_theme']));
    }

    // Automation specific
    if (isset($_POST['lead_magnet_url'])) {
        update_post_meta($profile_id, '_saas_lead_magnet_url', esc_url_raw($_POST['lead_magnet_url']));
        update_post_meta($profile_id, '_saas_lead_redirect', esc_url_raw($_POST['lead_redirect']));
        update_post_meta($profile_id, '_saas_lead_webhook', esc_url_raw($_POST['lead_webhook']));
        update_post_meta($profile_id, '_saas_lead_success_msg', sanitize_text_field($_POST['lead_success_msg']));

        update_post_meta($profile_id, '_saas_form_phone', isset($_POST['form_field_phone']) ? 1 : 0);
        update_post_meta($profile_id, '_saas_form_msg', isset($_POST['form_field_msg']) ? 1 : 0);

        update_post_meta($profile_id, '_saas_form_label_phone', sanitize_text_field($_POST['form_label_phone']));
        update_post_meta($profile_id, '_saas_form_label_msg', sanitize_text_field($_POST['form_label_msg']));
        update_post_meta($profile_id, '_saas_form_req_phone', isset($_POST['form_req_phone']) ? 1 : 0);
        update_post_meta($profile_id, '_saas_form_req_msg', isset($_POST['form_req_msg']) ? 1 : 0);
    }

    // SEO specific
    if (isset($_POST['meta_title'])) {
        update_post_meta($profile_id, '_saas_seo_title', sanitize_text_field($_POST['meta_title']));
        update_post_meta($profile_id, '_saas_seo_desc', sanitize_textarea_field($_POST['meta_desc']));
        update_post_meta($profile_id, '_saas_favicon', esc_url_raw($_POST['favicon']));
    }

    // Tracking specific
    if (isset($_POST['header_scripts'])) {
        update_post_meta($profile_id, '_saas_header_scripts', $_POST['header_scripts']);
        update_post_meta($profile_id, '_saas_footer_scripts', $_POST['footer_scripts']);
    }

    wp_send_json_success( 'Data saved' );
}

// 4. AJAX: Delete Link
add_action( 'wp_ajax_saas_delete_link', 'saas_ajax_delete_link' );
function saas_ajax_delete_link() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $link_id = intval( $_POST['link_id'] );
    $post = get_post( $link_id );

    if ( $post && $post->post_author == get_current_user_id() ) {
        wp_delete_post( $link_id, true );
        wp_send_json_success( 'Link deleted' );
    } else {
        wp_send_json_error( 'Unauthorized' );
    }
}

// 7. AJAX: Save Edited Link
add_action( 'wp_ajax_saas_save_link', 'saas_ajax_save_link' );
function saas_ajax_save_link() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $link_id = intval( $_POST['link_id'] );
    $title   = sanitize_text_field( $_POST['title'] );
    $url     = esc_url_raw( $_POST['url'] );
    $extra   = sanitize_textarea_field( $_POST['extra'] );

    $post = get_post( $link_id );
    if ( ! $post || $post->post_author != get_current_user_id() ) {
        wp_send_json_error( 'Unauthorized' );
    }

    wp_update_post([
        'ID'         => $link_id,
        'post_title' => $title,
    ]);

    update_post_meta( $link_id, '_saas_link_url', $url );

    if (isset($_POST['start_date'])) update_post_meta($link_id, '_saas_start_date', sanitize_text_field($_POST['start_date']));
    if (isset($_POST['end_date'])) update_post_meta($link_id, '_saas_end_date', sanitize_text_field($_POST['end_date']));
    if (isset($_POST['custom_bg'])) update_post_meta($link_id, '_saas_custom_bg', sanitize_hex_color($_POST['custom_bg']));
    if (isset($_POST['custom_text'])) update_post_meta($link_id, '_saas_custom_text', sanitize_hex_color($_POST['custom_text']));

    if (isset($_POST['url_mobile'])) update_post_meta($link_id, '_saas_url_mobile', esc_url_raw($_POST['url_mobile']));
    if (isset($_POST['url_geo'])) update_post_meta($link_id, '_saas_url_geo', esc_url_raw($_POST['url_geo']));
    if (isset($_POST['url_geo_country'])) update_post_meta($link_id, '_saas_url_geo_country', sanitize_text_field($_POST['url_geo_country']));
    if (isset($_POST['link_password'])) update_post_meta($link_id, '_saas_link_password', sanitize_text_field($_POST['link_password']));
    if (isset($_POST['block_style'])) update_post_meta($link_id, '_saas_block_style', sanitize_text_field($_POST['block_style']));
    if (isset($_POST['block_animation'])) update_post_meta($link_id, '_saas_block_animation', sanitize_text_field($_POST['block_animation']));
    if (isset($_POST['link_image_id'])) update_post_meta($link_id, '_saas_link_image_id', intval($_POST['link_image_id']));

    // Determine meta key based on type
    $type = get_post_meta( $link_id, '_saas_block_type', true );
    if ($type === 'testimonial') update_post_meta($link_id, '_saas_testimonial_text', $extra);
    elseif ($type === 'faq') update_post_meta($link_id, '_saas_faq_answer', $extra);
    elseif ($type === 'pricing' || $type === 'product') {
        update_post_meta($link_id, '_saas_price', $extra);
        if ($type === 'pricing') {
            $features = array_filter(array_map('trim', explode("\n", $_POST['extra'])));
            update_post_meta($link_id, '_saas_features', $features);
        }
    }
    elseif ($type === 'countdown') update_post_meta($link_id, '_saas_expiry', $extra);
    elseif ($type === 'milestone') {
        if (strpos($extra, ':') !== false) {
            list($lbl, $per) = explode(':', $extra, 2);
            update_post_meta($link_id, '_saas_ms_label', sanitize_text_field($lbl));
            update_post_meta($link_id, '_saas_ms_percent', intval($per));
        }
    }
    elseif ($type === 'social_icons') {
        $lines = array_filter(array_map('trim', explode("\n", $extra)));
        $data = [];
        foreach ($lines as $l) {
            if (strpos($l, ':') !== false) {
                list($p, $u) = explode(':', $l, 2);
                $data[trim($p)] = trim($u);
            }
        }
        update_post_meta($link_id, '_saas_social_data', $data);
    }

    wp_send_json_success( 'Link updated' );
}

// 6. AJAX: Apply Template
add_action( 'wp_ajax_saas_apply_template', 'saas_ajax_apply_template' );
function saas_ajax_apply_template() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $template = sanitize_text_field( $_POST['template'] );
    $user_id = get_current_user_id();
    $profile_id = isset($_POST['profile_id']) ? intval($_POST['profile_id']) : 0;

    // 1. Delete existing blocks for this user (or specifically for this profile if we had a relation, but for now we delete all user's links as per previous logic)
    $old_blocks = get_posts(['post_type' => 'saas_link', 'author' => $user_id, 'numberposts' => -1]);
    foreach ($old_blocks as $ob) wp_delete_post($ob->ID, true);

    // 2. Define Template Sets
    $sets = [
        'coach' => [
            'headline' => 'Helping you double your revenue in 90 days.',
            'bio' => 'Certified high-performance coach. I work with CEOs and founders to scale their impact.',
            'color' => '#6c5ce7',
            'theme' => 'light',
            'shadow' => 'soft',
            'links' => [
                ['title' => '👉 Free Strategy Session', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Watch Case Study', 'url' => 'https://youtube.com', 'type' => 'video'],
                ['title' => 'Client Success', 'url' => '#', 'type' => 'testimonial', 'extra' => 'Alex helped me double my revenue!'],
            ]
        ],
        'freelancer' => [
            'headline' => 'Design & Development for Modern Brands.',
            'bio' => 'Independent creative helping startups launch beautiful products.',
            'color' => '#00d1b2',
            'theme' => 'light',
            'shadow' => 'hard',
            'links' => [
                ['title' => 'My Portfolio', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/300\nhttps://via.placeholder.com/301"],
                ['title' => 'Hire Me', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
            ]
        ],
        'realtor' => [
            'headline' => 'Modern Homes for Modern Families.',
            'bio' => 'Helping buyers find their dream home in the luxury market. Top 1% agent.',
            'color' => '#2d3436',
            'theme' => 'dark',
            'shadow' => 'none',
            'links' => [
                ['title' => 'Available Listings', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/300\nhttps://via.placeholder.com/301"],
                ['title' => 'Book a Viewing', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Happy Homeowners', 'url' => '#', 'type' => 'testimonial', 'extra' => 'Found our dream home in record time!'],
                ['title' => 'Sales Target', 'url' => '#', 'type' => 'milestone', 'extra' => 'Closed:92']
            ]
        ],
        'business' => [
            'headline' => 'Innovative Solutions for Global Enterprise.',
            'bio' => 'Streamlining operations and driving growth through technology.',
            'color' => '#0073aa',
            'theme' => 'light',
            'shadow' => 'hard',
            'links' => [
                ['title' => 'Our Services', 'url' => '#', 'type' => 'pricing', 'extra' => "$99/hr\nFeature 1\nFeature 2"],
                ['title' => 'Book a Consultation', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Customer Feedback', 'url' => '#', 'type' => 'testimonial', 'extra' => 'Professional and reliable service.'],
                ['title' => 'Office Location', 'url' => 'https://maps.google.com', 'type' => 'button'],
                ['title' => 'FAQ', 'url' => '#', 'type' => 'faq', 'extra' => 'We operate 24/7 across the globe.']
            ]
        ],
        'politician' => [
            'headline' => 'A Stronger Community for a Brighter Future.',
            'bio' => 'Dedicated to transparency, progress, and public service.',
            'color' => '#e84118',
            'theme' => 'light',
            'shadow' => 'soft',
            'links' => [
                ['title' => 'Our Vision for 2024', 'url' => '#', 'type' => 'video'],
                ['title' => 'Donate to the Campaign', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Join the Volunteer Team', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Endorsements', 'url' => '#', 'type' => 'testimonial', 'extra' => 'A true leader for our community.'],
                ['title' => 'Fundraising Goal', 'url' => '#', 'type' => 'milestone', 'extra' => 'Goal:75']
            ]
        ],
        'elite_card' => [
            'headline' => 'John Doe | Executive Director',
            'bio' => 'Strategic visionary with 15+ years experience in digital transformation.',
            'color' => '#2c3e50',
            'theme' => 'dark',
            'shadow' => 'none',
            'links' => [
                ['title' => 'Contact Info', 'url' => '#', 'type' => 'social_icons', 'extra' => "phone:tel:123456\nemail:mailto:me@site.com\nlinkedin:https://linkedin.com"],
                ['title' => 'Save VCard', 'url' => home_url('/?saas_action=vcard'), 'type' => 'button', 'style' => 'rainbow'],
                ['title' => 'My Website', 'url' => 'https://yoursite.com', 'type' => 'button']
            ]
        ],
        'tiktok' => [
            'headline' => 'Daily Tech & Setup Inspo ⚡️',
            'bio' => 'Building the ultimate home office. Shop my setup below!',
            'color' => '#ff0050',
            'theme' => 'vibrant',
            'shadow' => 'hard',
            'links' => [
                ['title' => 'My Amazon Storefront', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
                ['title' => 'Flash Sale Ending Soon! ⏳', 'url' => '#', 'type' => 'countdown', 'extra' => date('Y-m-d H:i', strtotime('+12 hours'))],
                ['title' => 'Join My Discord', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'Latest Setup Tour', 'url' => '#', 'type' => 'video']
            ]
        ],
        'consultant' => [
            'headline' => 'Operational Efficiency for Modern SaaS.',
            'bio' => 'I help seed-stage startups optimize their unit economics and reduce churn.',
            'color' => '#2c3e50',
            'theme' => 'dark',
            'shadow' => 'none',
            'links' => [
                ['title' => 'Book an Audit', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Contact Details', 'url' => '#', 'type' => 'social_icons', 'extra' => "email:mailto:consult@site.com\nlinkedin:https://linkedin.com"],
                ['title' => 'Save to Contacts', 'url' => home_url('/?saas_action=vcard'), 'type' => 'button', 'style' => 'rainbow'],
                ['title' => 'Q4 Availability', 'url' => '#', 'type' => 'milestone', 'extra' => 'Booked:85']
            ]
        ]
    ];

    if ( isset($sets[$template]) ) {
        $set = $sets[$template];

        // Update profile meta too
        if ($profile_id) {
            update_post_meta($profile_id, '_saas_headline', $set['headline']);
            update_post_meta($profile_id, '_saas_bio', $set['bio']);
            update_post_meta($profile_id, '_saas_theme_color', $set['color']);
            update_post_meta($profile_id, '_saas_profile_theme', $set['theme']);
            update_post_meta($profile_id, '_saas_container_shadow', $set['shadow']);
        }

        foreach ( $set['links'] as $index => $b ) {
            $link_id = wp_insert_post(['post_type' => 'saas_link', 'post_title' => $b['title'], 'post_status' => 'publish', 'post_author' => $user_id]);
            update_post_meta($link_id, '_saas_block_type', $b['type']);
            update_post_meta($link_id, '_saas_link_url', $b['url']);
            update_post_meta($link_id, '_saas_priority', $index);
            if (isset($b['style'])) update_post_meta($link_id, '_saas_block_style', $b['style']);
            if (isset($b['extra'])) {
                $extra = $b['extra'];
                if ($b['type'] === 'testimonial') update_post_meta($link_id, '_saas_testimonial_text', $extra);
                if ($b['type'] === 'image_gallery') {
                    $urls = array_filter(array_map('trim', explode("\n", $extra)));
                    update_post_meta($link_id, '_saas_gallery_images', $urls);
                }
                if ($b['type'] === 'faq') update_post_meta($link_id, '_saas_faq_answer', $extra);
                if ($b['type'] === 'pricing' || $b['type'] === 'product') {
                    $lines = explode("\n", $extra);
                    update_post_meta($link_id, '_saas_price', $lines[0]);
                    if ($b['type'] === 'pricing') {
                        update_post_meta($link_id, '_saas_features', array_slice($lines, 1));
                    }
                }
                if ($b['type'] === 'milestone') {
                    if (strpos($extra, ':') !== false) {
                        list($lbl, $per) = explode(':', $extra, 2);
                        update_post_meta($link_id, '_saas_ms_label', $lbl);
                        update_post_meta($link_id, '_saas_ms_percent', intval($per));
                    }
                }
                if ($b['type'] === 'social_icons') {
                    $lines = array_filter(array_map('trim', explode("\n", $extra)));
                    $data = [];
                    foreach ($lines as $l) {
                        if (strpos($l, ':') !== false) {
                            list($p, $u) = explode(':', $l, 2);
                            $data[trim($p)] = trim($u);
                        }
                    }
                    update_post_meta($link_id, '_saas_social_data', $data);
                }
            }
        }
        wp_send_json_success('Template applied successfully');
    }

    wp_send_json_error('Invalid template');
}

// 12. AJAX: Export Analytics CSV
add_action( 'wp_ajax_saas_export_analytics', 'saas_ajax_export_analytics' );
function saas_ajax_export_analytics() {
    check_ajax_referer( 'saas_export_nonce', 'security' );

    $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'saas_analytics';
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT target_id, event_type, ip_address, created_at FROM $table WHERE user_id = %d", $user_id ) );

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="analytics.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Target Name', 'Event Type', 'IP Address', 'Date']);

    foreach ($results as $r) {
        $target_name = get_the_title($r->target_id) ?: 'Profile View';
        fputcsv($output, [$target_name, $r->event_type, $r->ip_address, $r->created_at]);
    }
    fclose($output);
    exit;
}

// 5. AJAX: Export Leads CSV
add_action( 'wp_ajax_saas_export_leads', 'saas_ajax_export_leads' );
function saas_ajax_export_leads() {
    check_ajax_referer( 'saas_export_nonce', 'security' );

    $user_id = get_current_user_id();
    $leads = get_posts([
        'post_type'   => 'saas_lead',
        'post_author' => $user_id,
        'numberposts' => -1,
    ]);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="leads.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Email', 'Phone', 'Message', 'Status', 'Date', 'Source Profile ID']);

    foreach ($leads as $lead) {
        fputcsv($output, [
            get_post_meta($lead->ID, '_saas_lead_name', true),
            get_post_meta($lead->ID, '_saas_lead_email', true),
            get_post_meta($lead->ID, '_saas_lead_phone', true),
            get_post_meta($lead->ID, '_saas_lead_message', true),
            get_post_meta($lead->ID, '_saas_lead_status', true) ?: 'New',
            get_the_date('Y-m-d H:i', $lead->ID),
            get_post_meta($lead->ID, '_saas_lead_source_id', true),
        ]);
    }
    fclose($output);
    exit;
}

// 8. AJAX: Get Lead Details
add_action( 'wp_ajax_saas_get_lead_details', 'saas_ajax_get_lead_details' );
function saas_ajax_get_lead_details() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $lead_id = intval( $_POST['lead_id'] );
    $lead = get_post( $lead_id );

    if ( ! $lead || $lead->post_type !== 'saas_lead' || $lead->post_author != get_current_user_id() ) {
        wp_send_json_error( 'Unauthorized' );
    }

    $name = get_post_meta($lead_id, '_saas_lead_name', true);
    $email = get_post_meta($lead_id, '_saas_lead_email', true);
    $phone = get_post_meta($lead_id, '_saas_lead_phone', true);
    $msg = get_post_meta($lead_id, '_saas_lead_message', true);
    $block_id = get_post_meta($lead_id, '_saas_lead_block_id', true);
    $status = get_post_meta($lead_id, '_saas_lead_status', true) ?: 'New';
    $notes = get_post_meta($lead_id, '_saas_lead_notes', true);
    $tags = get_post_meta($lead_id, '_saas_lead_tags', true);
    if (is_array($tags)) $tags = implode(', ', $tags);

    ob_start();
    ?>
    <div class="lead-detail-view">
        <p><strong>Name:</strong> <?php echo esc_html($name); ?></p>
        <p><strong>Email:</strong> <?php echo esc_html($email); ?></p>
        <?php if($phone) : ?><p><strong>Phone:</strong> <?php echo esc_html($phone); ?></p><?php endif; ?>
        <?php if($msg) : ?><p><strong>Message:</strong> <br><?php echo nl2br(esc_html($msg)); ?></p><?php endif; ?>
        <?php if($block_id) : ?>
            <p><strong>Source Block:</strong> <?php echo get_the_title($block_id); ?> (ID: <?php echo $block_id; ?>)</p>
        <?php endif; ?>
        <p><strong>Date:</strong> <?php echo get_the_date('F j, Y g:i a', $lead_id); ?></p>
        <hr>
        <form id="saas-update-lead-form">
            <input type="hidden" name="lead_id" value="<?php echo $lead_id; ?>">
            <div class="field">
                <label>Status</label>
                <select name="status">
                    <option value="New" <?php selected($status, 'New'); ?>>New</option>
                    <option value="Contacted" <?php selected($status, 'Contacted'); ?>>Contacted</option>
                    <option value="Converted" <?php selected($status, 'Converted'); ?>>Converted</option>
                </select>
            </div>
            <div class="field">
                <label>Tags (comma separated)</label>
                <input type="text" name="tags" value="<?php echo esc_attr($tags); ?>">
            </div>
            <div class="field">
                <label>Internal Notes</label>
                <textarea name="notes" rows="4"><?php echo esc_textarea($notes); ?></textarea>
            </div>
            <button type="submit" class="button button-primary">Update Lead</button>
        </form>
    </div>
    <?php
    wp_send_json_success( ob_get_clean() );
}

// 9. AJAX: Update Lead
add_action( 'wp_ajax_saas_update_lead', 'saas_ajax_update_lead' );
function saas_ajax_update_lead() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $lead_id = intval( $_POST['lead_id'] );
    $status = sanitize_text_field( $_POST['status'] );
    $notes = sanitize_textarea_field( $_POST['notes'] );
    $tags = array_map('trim', explode(',', sanitize_text_field($_POST['tags'])));

    $lead = get_post( $lead_id );
    if ( ! $lead || $lead->post_author != get_current_user_id() ) {
        wp_send_json_error( 'Unauthorized' );
    }

    update_post_meta($lead_id, '_saas_lead_status', $status);
    update_post_meta($lead_id, '_saas_lead_notes', $notes);
    update_post_meta($lead_id, '_saas_lead_tags', $tags);

    wp_send_json_success( 'Lead updated successfully' );
}

// 10. AJAX: Delete Lead
add_action( 'wp_ajax_saas_delete_lead', 'saas_ajax_delete_lead' );
function saas_ajax_delete_lead() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $lead_id = intval( $_POST['lead_id'] );
    $lead = get_post( $lead_id );

    if ( ! $lead || $lead->post_type !== 'saas_lead' || $lead->post_author != get_current_user_id() ) {
        wp_send_json_error( 'Unauthorized' );
    }

    wp_delete_post( $lead_id, true );
    wp_send_json_success( 'Lead deleted' );
}

// 13. AJAX: Create New Profile
add_action( 'wp_ajax_saas_create_profile', 'saas_ajax_create_profile' );
function saas_ajax_create_profile() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );
    $user_id = get_current_user_id();

    // Limit free users to 1 profile
    $payments = new Saas_Payments();
    $existing = get_posts(['post_type' => 'saas_profile', 'author' => $user_id, 'numberposts' => -1]);
    if ( count($existing) >= 1 && !$payments->is_pro_user($user_id) ) {
        wp_send_json_error( 'Free users are limited to 1 profile. Upgrade to Pro for unlimited profiles.' );
    }

    $title = sanitize_text_field( $_POST['profile_title'] );
    if ( empty($title) ) wp_send_json_error( 'Title required' );

    $profile_id = wp_insert_post([
        'post_type'   => 'saas_profile',
        'post_title'  => $title,
        'post_status' => 'publish',
        'post_author' => $user_id,
    ]);

    if ( ! is_wp_error($profile_id) ) {
        wp_send_json_success([ 'id' => $profile_id, 'url' => get_permalink($profile_id) ]);
    } else {
        wp_send_json_error( 'Failed to create profile' );
    }
}

// 14. AJAX: Generate Sample Data
add_action( 'wp_ajax_saas_generate_samples', 'saas_ajax_generate_samples' );
function saas_ajax_generate_samples() {
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error('Unauthorized');

    $user_id = get_current_user_id();
    $samples = [
        [
            'title' => 'Elite Business Coach',
            'headline' => 'Scaling Founders from 6 to 7 Figures 🚀',
            'bio' => 'Ex-Google Exec turned Strategic Coach. I help high-ticket service providers automate their acquisition and double their profit margins.',
            'color' => '#6c5ce7',
            'theme' => 'light',
            'shadow' => 'soft',
            'links' => [
                ['t' => '👉 Free Strategy Session', 'u' => '#', 'type' => 'button', 'style' => 'featured'],
                ['t' => 'Masterclass: Scaling Systems', 'u' => 'https://youtube.com', 'type' => 'video'],
                ['t' => 'Client Success Stories', 'u' => '#', 'type' => 'testimonial', 'extra' => 'Working with Alex was the best decision for my agency. We hit $100k months in record time.'],
                ['t' => 'Consulting Packages', 'u' => '#', 'type' => 'pricing', 'extra' => "$2,500/mo\nBi-weekly Calls\nSlack Support\nResource Library"],
            ]
        ],
        [
            'title' => 'TikTok Affiliate Pro',
            'headline' => 'Shop My Top Tech & Setup Finds 🛍️',
            'bio' => 'Sharing the best tech deals and home office aesthetic finds. Check the links below for exclusive discounts!',
            'color' => '#E1306C',
            'theme' => 'vibrant',
            'shadow' => 'hard',
            'links' => [
                ['t' => 'My Amazon Storefront', 'u' => 'https://amazon.com', 'type' => 'button', 'style' => 'rainbow'],
                ['t' => 'Flash Sale Ending Soon! ⏳', 'u' => '#', 'type' => 'countdown', 'extra' => date('Y-m-d H:i', strtotime('+12 hours'))],
                ['t' => 'Join Private Deals Telegram', 'u' => '#', 'type' => 'button', 'style' => 'glow'],
                ['t' => 'Setup Tour', 'u' => 'https://tiktok.com', 'type' => 'video'],
            ]
        ],
        [
            'title' => 'Luxury Real Estate',
            'headline' => 'Bespoke Advisory for Elite Homeowners.',
            'bio' => 'Specializing in off-market luxury listings in the Tri-State area. Member of the Top 0.1% Global Network.',
            'color' => '#2d3436',
            'theme' => 'dark',
            'shadow' => 'none',
            'links' => [
                ['t' => 'New Off-Market Listings', 'u' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/800x600?text=Penthouse+A\nhttps://via.placeholder.com/800x600?text=Coastal+Villa"],
                ['t' => 'Request Private Showing', 'u' => '#', 'type' => 'lead_form'],
                ['t' => 'Quarterly Market Report', 'u' => '#', 'type' => 'button', 'style' => 'featured'],
                ['t' => 'Q2 Sales Achievement', 'u' => '#', 'type' => 'milestone', 'extra' => 'Volume:$42M']
            ]
        ],
        [
            'title' => 'Creative Freelancer',
            'headline' => 'Visual Identity & Web Experience Design.',
            'bio' => 'Helping DTC brands stand out through minimalist design and high-converting interfaces.',
            'color' => '#00d1b2',
            'theme' => 'light',
            'shadow' => 'hard',
            'links' => [
                ['t' => 'Recent Branding Work', 'u' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400\nhttps://via.placeholder.com/401\nhttps://via.placeholder.com/402"],
                ['t' => 'Project Inquiry Form', 'u' => '#', 'type' => 'lead_form'],
                ['t' => 'View Pricing Guide', 'u' => '#', 'type' => 'pricing', 'extra' => "$1,500+\nCustom Branding\nUI/UX Design\nWebflow Dev"],
            ]
        ],
        [
            'title' => 'Campaign HQ 2024',
            'headline' => 'A New Vision for Our Community.',
            'bio' => 'Join the movement for transparency, sustainable growth, and better schools. Every voice matters.',
            'color' => '#e84118',
            'theme' => 'light',
            'shadow' => 'soft',
            'links' => [
                ['t' => 'Watch the Keynote Speech', 'u' => 'https://youtube.com', 'type' => 'video'],
                ['t' => 'Donate to the Campaign', 'u' => '#', 'type' => 'button', 'style' => 'featured'],
                ['t' => 'Volunteer Signup', 'u' => '#', 'type' => 'lead_form'],
                ['t' => 'Endorsements', 'u' => '#', 'type' => 'testimonial', 'extra' => 'The only candidate with a clear plan for our future.'],
                ['t' => 'Grassroots Funding Progress', 'u' => '#', 'type' => 'milestone', 'extra' => 'Goal:82']
            ]
        ],
        [
            'title' => 'John Doe Consulting',
            'headline' => 'Operational Efficiency for Modern SaaS.',
            'bio' => 'Ex-SaaS Founder helping seed-stage startups optimize their unit economics and reduce churn.',
            'color' => '#2c3e50',
            'theme' => 'dark',
            'shadow' => 'none',
            'links' => [
                ['t' => 'Contact Details', 'u' => '#', 'type' => 'social_icons', 'extra' => "email:mailto:john@doe.com\nlinkedin:https://linkedin.com/in/johndoe\ntwitter:https://twitter.com/johndoe"],
                ['t' => 'Save to Contacts', 'u' => home_url('/?saas_action=vcard'), 'type' => 'button', 'style' => 'rainbow'],
                ['t' => 'Schedule Audit Call', 'u' => '#', 'type' => 'button', 'style' => 'featured'],
            ]
        ]
    ];

    foreach ($samples as $s) {
        $p_id = wp_insert_post(['post_type' => 'saas_profile', 'post_title' => $s['title'], 'post_status' => 'publish', 'post_author' => $user_id]);
        update_post_meta($p_id, '_saas_headline', $s['headline']);
        update_post_meta($p_id, '_saas_bio', $s['bio']);
        update_post_meta($p_id, '_saas_theme_color', $s['color']);
        update_post_meta($p_id, '_saas_profile_theme', $s['theme']);
        update_post_meta($p_id, '_saas_container_shadow', $s['shadow']);

        foreach ($s['links'] as $idx => $l) {
            $l_id = wp_insert_post(['post_type' => 'saas_link', 'post_title' => $l['t'], 'post_status' => 'publish', 'post_author' => $user_id]);
            update_post_meta($l_id, '_saas_link_url', $l['u']);
            update_post_meta($l_id, '_saas_block_type', $l['type']);
            update_post_meta($l_id, '_saas_priority', $idx);
            if (isset($l['style'])) update_post_meta($l_id, '_saas_block_style', $l['style']);
            if (isset($l['extra'])) {
                if ($l['type'] === 'testimonial') update_post_meta($l_id, '_saas_testimonial_text', $l['extra']);
                if ($l['type'] === 'image_gallery') update_post_meta($l_id, '_saas_gallery_images', explode("\n", $l['extra']));
                if ($l['type'] === 'countdown') update_post_meta($l_id, '_saas_expiry', $l['extra']);
                if ($l['type'] === 'milestone') {
                    list($lbl, $per) = explode(':', $l['extra']);
                    update_post_meta($l_id, '_saas_ms_label', $lbl);
                    update_post_meta($l_id, '_saas_ms_percent', intval($per));
                }
            }
        }
    }

    wp_send_json_success('Sample profiles created successfully!');
}

// 11. AJAX: Verify Link Password (Secure)
add_action( 'wp_ajax_saas_verify_link_password', 'saas_ajax_verify_link_password' );
add_action( 'wp_ajax_nopriv_saas_verify_link_password', 'saas_ajax_verify_link_password' );
function saas_ajax_verify_link_password() {
    $link_id = intval( $_POST['link_id'] );
    $password = $_POST['password'] ?? '';

    $saved_pass = get_post_meta( $link_id, '_saas_link_password', true );
    $target_url = get_post_meta( $link_id, '_saas_link_url', true );

    if ( $saved_pass && $password === $saved_pass ) {
        wp_send_json_success([ 'url' => esc_url($target_url) ]);
    } else {
        wp_send_json_error( 'Incorrect password' );
    }
}

// 15. AJAX: Test Webhook
add_action( 'wp_ajax_saas_test_webhook', 'saas_ajax_test_webhook' );
function saas_ajax_test_webhook() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $webhook_url = esc_url_raw( $_POST['webhook_url'] );
    if ( ! $webhook_url ) wp_send_json_error( 'Missing URL' );

    $response = wp_remote_post( $webhook_url, [
        'body' => [
            'test' => true,
            'message' => 'SaaS Webhook Test Success 🚀',
            'name' => 'John Doe (Test)',
            'email' => 'test@site.com'
        ]
    ]);

    if ( is_wp_error($response) ) {
        wp_send_json_error( 'Webhook Failed: ' . $response->get_error_message() );
    }

    wp_send_json_success( 'Webhook Triggered Successfully!' );
}
