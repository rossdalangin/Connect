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
            wp_update_post([
                'ID'         => $id,
                'menu_order' => $index
            ]);
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
    $profile_id = intval( $_POST['profile_id'] );
    $type  = sanitize_text_field( $_POST['block_type'] );
    $style = isset($_POST['block_style']) ? sanitize_text_field( $_POST['block_style'] ) : 'regular';
    $animation = isset($_POST['block_animation']) ? sanitize_text_field( $_POST['block_animation'] ) : 'fadeinup';

    // Pro-tier Block Enforcement
    $pro_only_blocks = ['image_gallery', 'newsletter', 'calendar', 'countdown', 'product'];
    $payments = new Saas_Payments();
    if (in_array($type, $pro_only_blocks) && !$payments->is_pro_user(get_current_user_id())) {
        wp_send_json_error('This block type is reserved for Elite Pro or Agency users.');
    }

    // Verify ownership of the target profile
    $profile = get_post($profile_id);
    if (!$profile || $profile->post_author != get_current_user_id()) {
        wp_send_json_error('Unauthorized profile access');
    }

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
        update_post_meta( $link_id, '_saas_profile_id', $profile_id );
        update_post_meta( $link_id, '_saas_block_type', $type );
        update_post_meta( $link_id, '_saas_block_style', $style );
        update_post_meta( $link_id, '_saas_block_animation', $animation );
        update_post_meta( $link_id, '_saas_link_url', $url );

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
    $profile_id = intval($_POST['profile_id']);
    $context = sanitize_text_field($_POST['form_context'] ?? 'all');

    // Verify ownership
    $profile = get_post( $profile_id );
    if ( ! $profile || $profile->post_author != $user_id ) {
        wp_send_json_error( 'Unauthorized' );
    }

    // IDENTITY & PROFILE CONTEXT
    if ($context === 'profile') {
        $payments = new Saas_Payments();
        $is_pro = $payments->is_pro_user($user_id);

        if (isset($_POST['headline'])) update_post_meta($profile_id, '_saas_headline', sanitize_text_field($_POST['headline']));
        if (isset($_POST['bio'])) update_post_meta($profile_id, '_saas_bio', sanitize_textarea_field($_POST['bio']));
        if (isset($_POST['niche'])) update_post_meta($profile_id, '_saas_niche', sanitize_text_field($_POST['niche']));
        if (isset($_POST['phone'])) update_post_meta($profile_id, '_saas_phone', sanitize_text_field($_POST['phone']));
        if (isset($_POST['company'])) update_post_meta($profile_id, '_saas_company', sanitize_text_field($_POST['company']));

        if ($is_pro) {
            if (isset($_POST['custom_domain'])) update_post_meta($profile_id, '_saas_custom_domain', sanitize_text_field($_POST['custom_domain']));
            if (isset($_POST['profile_password'])) update_post_meta($profile_id, '_saas_profile_password', sanitize_text_field($_POST['profile_password']));
            update_post_meta($profile_id, '_saas_verified_badge', isset($_POST['verified_badge']) ? '1' : '0');
        } else {
            // Force disable pro-only fields for free users
            update_post_meta($profile_id, '_saas_verified_badge', '0');
            delete_post_meta($profile_id, '_saas_custom_domain');
            delete_post_meta($profile_id, '_saas_profile_password');
        }

        update_post_meta($profile_id, '_saas_show_in_directory', isset($_POST['show_in_directory']) ? '1' : '0');
        if (isset($_POST['profile_image_id'])) set_post_thumbnail($profile_id, intval($_POST['profile_image_id']));
        if (isset($_POST['cover_image_id'])) update_post_meta($profile_id, '_saas_cover_id', intval($_POST['cover_image_id']));

        // Update Slug (Username)
        if (isset($_POST['profile_slug'])) {
            $new_slug = sanitize_title($_POST['profile_slug']);
            if ($new_slug && $new_slug !== $profile->post_name) {
                $exists = get_posts(['name' => $new_slug, 'post_type' => 'saas_profile', 'post_status' => 'publish', 'numberposts' => 1]);
                if (empty($exists)) wp_update_post(['ID' => $profile_id, 'post_name' => $new_slug]);
            }
        }
    }

    // BRANDING CONTEXT
    if ($context === 'branding') {
        $payments = new Saas_Payments();
        $is_pro = $payments->is_pro_user($user_id);

        if (isset($_POST['theme_color'])) update_post_meta($profile_id, '_saas_theme_color', sanitize_hex_color($_POST['theme_color']));
        if (isset($_POST['profile_theme'])) update_post_meta($profile_id, '_saas_profile_theme', sanitize_text_field($_POST['profile_theme']));
        if (isset($_POST['font_family'])) update_post_meta($profile_id, '_saas_font_family', sanitize_text_field($_POST['font_family']));
        if (isset($_POST['container_shadow'])) update_post_meta($profile_id, '_saas_container_shadow', sanitize_text_field($_POST['container_shadow']));
        if (isset($_POST['btn_shape'])) update_post_meta($profile_id, '_saas_btn_shape', sanitize_text_field($_POST['btn_shape']));

        if ($is_pro) {
            if (isset($_POST['custom_css'])) update_post_meta($profile_id, '_saas_custom_css', $_POST['custom_css']);
            update_post_meta($profile_id, '_saas_hide_branding', isset($_POST['hide_branding']) ? '1' : '0');
        } else {
            // Force disable pro-only styles for free users
            delete_post_meta($profile_id, '_saas_custom_css');
            update_post_meta($profile_id, '_saas_hide_branding', '0');
        }

        if (isset($_POST['bg_type'])) {
            $bg_type = sanitize_text_field( $_POST['bg_type'] );
            $bg_val  = sanitize_text_field( $_POST['bg_value'] );
            update_post_meta($profile_id, '_saas_bg_type', $bg_type );
            if ($bg_type === 'gradient') update_post_meta($profile_id, '_saas_bg_gradient', $bg_val );
            else update_post_meta($profile_id, '_saas_bg_color', $bg_val );
        }

        update_post_meta($profile_id, '_saas_social_proof', isset($_POST['social_proof']) ? '1' : '0');
    }

    // QR CONTEXT
    if ($context === 'qr' && isset($_POST['qr_color'])) {
        update_post_meta($profile_id, '_saas_qr_color', sanitize_text_field($_POST['qr_color']));
    }

    // AUTOMATION CONTEXT
    if ($context === 'automation') {
        if (isset($_POST['lead_magnet_url'])) update_post_meta($profile_id, '_saas_lead_magnet_url', esc_url_raw($_POST['lead_magnet_url']));
        if (isset($_POST['lead_redirect'])) update_post_meta($profile_id, '_saas_lead_redirect', esc_url_raw($_POST['lead_redirect']));
        if (isset($_POST['lead_webhook'])) update_post_meta($profile_id, '_saas_lead_webhook', esc_url_raw($_POST['lead_webhook']));
        if (isset($_POST['lead_success_msg'])) update_post_meta($profile_id, '_saas_lead_success_msg', sanitize_text_field($_POST['lead_success_msg']));

        update_post_meta($profile_id, '_saas_form_phone', isset($_POST['form_field_phone']) ? '1' : '0');
        update_post_meta($profile_id, '_saas_form_msg', isset($_POST['form_field_msg']) ? '1' : '0');
        update_post_meta($profile_id, '_saas_form_req_phone', isset($_POST['form_req_phone']) ? '1' : '0');
        update_post_meta($profile_id, '_saas_form_req_msg', isset($_POST['form_req_msg']) ? '1' : '0');
        update_post_meta($profile_id, '_saas_lead_auto_respond', isset($_POST['lead_auto_respond']) ? '1' : '0');

        if (isset($_POST['form_label_phone'])) update_post_meta($profile_id, '_saas_form_label_phone', sanitize_text_field($_POST['form_label_phone']));
        if (isset($_POST['form_label_msg'])) update_post_meta($profile_id, '_saas_form_label_msg', sanitize_text_field($_POST['form_label_msg']));
        if (isset($_POST['lead_auto_msg'])) update_post_meta($profile_id, '_saas_lead_auto_msg', sanitize_textarea_field($_POST['lead_auto_msg']));
    }

    // ALL/WIZARD CONTEXT (fallback)
    if ($context === 'all') {
        if (isset($_POST['headline'])) update_post_meta($profile_id, '_saas_headline', sanitize_text_field($_POST['headline']));
        if (isset($_POST['bio'])) update_post_meta($profile_id, '_saas_bio', sanitize_textarea_field($_POST['bio']));
        if (isset($_POST['niche'])) update_post_meta($profile_id, '_saas_niche', sanitize_text_field($_POST['niche']));
        if (isset($_POST['theme_color'])) update_post_meta($profile_id, '_saas_theme_color', sanitize_hex_color($_POST['theme_color']));
    }

    // SEO CONTEXT
    if ($context === 'seo' || $context === 'all') {
        if (isset($_POST['meta_title'])) update_post_meta($profile_id, '_saas_seo_title', sanitize_text_field($_POST['meta_title']));
        if (isset($_POST['meta_desc'])) update_post_meta($profile_id, '_saas_seo_desc', sanitize_textarea_field($_POST['meta_desc']));
        if (isset($_POST['favicon'])) update_post_meta($profile_id, '_saas_favicon', esc_url_raw($_POST['favicon']));
    }

    // INTEGRATIONS CONTEXT
    if ($context === 'integrations' || $context === 'all') {
        if (isset($_POST['mailchimp_api'])) update_post_meta($profile_id, '_saas_mailchimp_api', sanitize_text_field($_POST['mailchimp_api']));
        if (isset($_POST['mailchimp_list'])) update_post_meta($profile_id, '_saas_mailchimp_list', sanitize_text_field($_POST['mailchimp_list']));
        if (isset($_POST['hubspot_token'])) update_post_meta($profile_id, '_saas_hubspot_token', sanitize_text_field($_POST['hubspot_token']));
    }

    // TRACKING CONTEXT
    if ($context === 'tracking' || $context === 'all') {
        if (isset($_POST['header_scripts'])) update_post_meta($profile_id, '_saas_header_scripts', $_POST['header_scripts']);
        if (isset($_POST['footer_scripts'])) update_post_meta($profile_id, '_saas_footer_scripts', $_POST['footer_scripts']);
    }

    wp_send_json_success( 'Data saved successfully!' );
}

// 27. AJAX: Save Account Settings
add_action( 'wp_ajax_saas_save_account', 'saas_ajax_save_account' );
function saas_ajax_save_account() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );
    $user_id = get_current_user_id();

    $display_name = sanitize_text_field( $_POST['display_name'] );
    $user_email   = sanitize_email( $_POST['user_email'] );
    $new_password = $_POST['new_password'];

    $userdata = [
        'ID'           => $user_id,
        'display_name' => $display_name,
        'user_email'   => $user_email,
    ];

    if ( ! empty($new_password) ) {
        $userdata['user_pass'] = $new_password;
    }

    $updated = wp_update_user( $userdata );

    if ( is_wp_error($updated) ) {
        wp_send_json_error( $updated->get_error_message() );
    }

    wp_send_json_success( 'Account settings updated successfully!' );
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
    if (isset($_POST['ab_title_b'])) update_post_meta($link_id, '_saas_ab_title_b', sanitize_text_field($_POST['ab_title_b']));
    if (isset($_POST['ab_url_b'])) update_post_meta($link_id, '_saas_ab_url_b', esc_url_raw($_POST['ab_url_b']));
    if (isset($_POST['hour_from'])) update_post_meta($link_id, '_saas_hour_from', sanitize_text_field($_POST['hour_from']));
    if (isset($_POST['hour_to'])) update_post_meta($link_id, '_saas_hour_to', sanitize_text_field($_POST['hour_to']));

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

    // Verify profile ownership
    if ($profile_id) {
        $profile = get_post($profile_id);
        if (!$profile || $profile->post_author != $user_id) {
            wp_send_json_error('Unauthorized profile access');
        }
    }

    // 1. Delete existing blocks ONLY for this specific profile
    if ($profile_id) {
        $old_blocks = get_posts([
            'post_type' => 'saas_link',
            'author'    => $user_id,
            'meta_key'  => '_saas_profile_id',
            'meta_value' => $profile_id,
            'numberposts' => -1
        ]);
        foreach ($old_blocks as $ob) wp_delete_post($ob->ID, true);
    }

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
        'servant' => [
            'headline' => 'Dedicated to Progress & Community Service 🏛️',
            'bio' => 'Serving as your advocate in public office. I am dedicated to sustainable growth and fiscal responsibility.',
            'color' => '#1e293b',
            'theme' => 'light',
            'shadow' => 'soft',
            'links' => [
                ['title' => 'Latest Community Update', 'url' => '#', 'type' => 'video'],
                ['title' => 'Join the Volunteer Team', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'My Vision for 2024', 'url' => '#', 'type' => 'button', 'style' => 'outline'],
            ]
        ],
        'creator' => [
            'headline' => 'Building the Next Generation of Digital Brands 🎬',
            'bio' => 'Daily tips on content strategy and community building. Join 50k+ others on the journey.',
            'color' => '#ff0050',
            'theme' => 'vibrant',
            'shadow' => 'hard',
            'links' => [
                ['title' => 'Latest Masterclass', 'url' => '#', 'type' => 'video'],
                ['title' => 'Join Private Discord', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'My Setup Gear', 'url' => '#', 'type' => 'button'],
            ]
        ],
        'speaker' => [
            'headline' => 'Inspiring Transformation through Keynotes 🎙️',
            'bio' => 'Helping organizations navigate change and build resilient cultures. Global Keynote Speaker.',
            'color' => '#d4af37',
            'theme' => 'luxury',
            'shadow' => 'soft',
            'links' => [
                ['title' => 'Watch Highlight Reel', 'url' => '#', 'type' => 'video'],
                ['title' => 'Inquire for Speaking', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Speaker One-Sheet', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
            ]
        ],
        'author' => [
            'headline' => 'Exploring the Intersection of Tech & Humanity ✍️',
            'bio' => 'Bestselling Author. My work helps modern professionals build meaningful lives in an era of distraction.',
            'color' => '#4338ca',
            'theme' => 'light',
            'shadow' => 'soft',
            'links' => [
                ['title' => 'Order New Book', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Read Chapter Preview', 'url' => '#', 'type' => 'button'],
                ['title' => 'Join my Newsletter', 'url' => '#', 'type' => 'newsletter'],
            ]
        ],
        'consultant' => [
            'headline' => 'Operational Efficiency for Modern SaaS 🧠',
            'bio' => 'I help seed-stage startups optimize their unit economics and reduce churn.',
            'color' => '#2c3e50',
            'theme' => 'dark',
            'shadow' => 'none',
            'links' => [
                ['title' => 'Book an Audit', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Case Studies', 'url' => '#', 'type' => 'button'],
                ['title' => 'Contact Details', 'url' => '#', 'type' => 'social_icons', 'extra' => "email:mailto:consult@site.com\nlinkedin:https://linkedin.com"],
            ]
        ],
        'lawyer' => [
            'headline' => 'Strategic Legal Advocacy for Elite Clients ⚖️',
            'bio' => 'Providing expert legal counsel with a focus on results. High-stakes litigation and advisory.',
            'color' => '#1a1a1a',
            'theme' => 'luxury',
            'shadow' => 'hard',
            'links' => [
                ['title' => 'Request Consultation', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Areas of Practice', 'url' => '#', 'type' => 'button', 'style' => 'outline'],
                ['title' => 'Client Testimonials', 'url' => '#', 'type' => 'testimonial', 'extra' => 'Unparalleled expertise and results.'],
            ]
        ],
        'doctor' => [
            'headline' => 'Compassionate Care, Precision Medicine 🩺',
            'bio' => 'Your Partner in Health and Longevity. Evidence-based wellness for modern lives.',
            'color' => '#0d9488',
            'theme' => 'light',
            'shadow' => 'soft',
            'links' => [
                ['title' => 'Book Appointment', 'url' => '#', 'type' => 'calendar'],
                ['title' => 'Patient Portal', 'url' => '#', 'type' => 'button'],
                ['title' => 'Health Resources', 'url' => '#', 'type' => 'button', 'style' => 'outline'],
            ]
        ],
        'artist' => [
            'headline' => 'Visual Storytelling through Digital Art 🎨',
            'bio' => 'Independent designer creating immersive visual experiences for forward-thinking brands.',
            'color' => '#f472b6',
            'theme' => 'vibrant',
            'shadow' => 'hard',
            'links' => [
                ['title' => 'Portfolio Gallery', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400\nhttps://via.placeholder.com/401"],
                ['title' => 'Project Inquiry', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Follow my Process', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
            ]
        ],
        'agency' => [
            'headline' => 'Scaling Brands through Performance Marketing 🏢',
            'bio' => 'We build high-performance funnels that drive revenue for elite founders.',
            'color' => '#111827',
            'theme' => 'dark',
            'shadow' => 'soft',
            'links' => [
                ['title' => 'Get a Free Quote', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Our Pricing Models', 'url' => '#', 'type' => 'pricing', 'extra' => "$2,500+\nFull CRM Sync\nScale Strategy"],
                ['title' => 'Latest Campaign Results', 'url' => '#', 'type' => 'video'],
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
        ],
        'luxury' => [
            'headline' => 'Bespoke Private Advisory.',
            'bio' => 'Curating exclusive opportunities for the discerning individual.',
            'color' => '#d4af37',
            'theme' => 'dark',
            'shadow' => 'soft',
            'links' => [
                ['title' => 'Inquire Privately', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Exclusive Portfolio', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/800x600?text=Asset+1\nhttps://via.placeholder.com/800x600?text=Asset+2"],
                ['title' => 'Secure Documentation', 'url' => '#', 'type' => 'button', 'style' => 'outline', 'extra' => 'Password Protected']
            ]
        ]
    ];

    // Ensure template exists, fallback to business or coach if not
    if (!isset($sets[$template])) {
        if (isset($sets['business'])) $template = 'business';
        elseif (isset($sets['coach'])) $template = 'coach';
    }

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
            update_post_meta($link_id, '_saas_profile_id', $profile_id); // Associate with profile
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

    if ($results) {
        foreach ($results as $r) {
            $target_name = get_the_title($r->target_id) ?: 'Profile View';
            fputcsv($output, [$target_name, $r->event_type, $r->ip_address, $r->created_at]);
        }
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
        'author'      => $user_id,
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
    $log = get_post_meta($lead_id, '_saas_lead_log', true) ?: [];

    ob_start();
    ?>
    <div class="lead-detail-view">
        <p><strong>Name:</strong> <?php echo esc_html($name); ?></p>
        <p><strong>Email:</strong> <?php echo esc_html($email); ?></p>
        <?php if($phone) : ?><p><strong>Phone:</strong> <?php echo esc_html($phone); ?></p><?php endif; ?>
        <?php if($msg) : ?><p><strong>Message:</strong> <br><?php echo nl2br(esc_html($msg)); ?></p><?php endif; ?>
        <p><strong>Date:</strong> <?php echo get_the_date('F j, Y g:i a', $lead_id); ?></p>
        <hr>
        <h4>Quick Email to Lead</h4>
        <form id="saas-email-lead-form" style="margin-bottom:20px;">
            <input type="hidden" name="lead_id" value="<?php echo $lead_id; ?>">
            <div class="field"><label>Subject</label><input type="text" name="subject" value="Regarding your inquiry" required></div>
            <div class="field"><label>Message</label><textarea name="message" rows="3" required></textarea></div>
            <button type="submit" class="button">✉️ Send Email</button>
        </form>
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
                <label>Internal Notes</label>
                <textarea name="notes" rows="3"><?php echo esc_textarea($notes); ?></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">Update Status & Notes</button>
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

    // Activity Logging
    $log = get_post_meta($lead_id, '_saas_lead_log', true) ?: [];
    $log[] = [
        'time' => current_time('mysql'),
        'msg'  => "Lead updated to $status status. Notes saved."
    ];
    update_post_meta($lead_id, '_saas_lead_log', array_slice($log, -10)); // Keep last 10

    wp_send_json_success( 'Lead updated successfully' );
}


// 23. AJAX: Email Lead
add_action( 'wp_ajax_saas_email_lead', 'saas_ajax_email_lead' );
function saas_ajax_email_lead() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $lead_id = intval( $_POST['lead_id'] );
    $subject = sanitize_text_field( $_POST['subject'] );
    $message = sanitize_textarea_field( $_POST['message'] );

    $lead = get_post( $lead_id );
    if ( ! $lead || $lead->post_author != get_current_user_id() ) wp_send_json_error('Unauthorized');

    $lead_email = get_post_meta($lead_id, '_saas_lead_email', true);
    if ( ! $lead_email ) wp_send_json_error('Lead email not found');

    $user = wp_get_current_user();
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $user->display_name . ' <' . $user->user_email . '>'
    ];

    $body = "
        <div style='font-family:sans-serif; padding:30px; background:#f8fafc; border-radius:20px;'>
            <div style='background:#fff; padding:30px; border-radius:15px; border:1px solid #e2e8f0;'>
                " . wpautop($message) . "
            </div>
            <p style='font-size:0.8rem; color:#64748b; margin-top:20px;'>
                Sent by " . esc_html($user->display_name) . " via elite SaaS platform.
            </p>
        </div>
    ";

    if ( wp_mail( $lead_email, $subject, $body, $headers ) ) {
        // Log the activity
        $log = get_post_meta($lead_id, '_saas_lead_log', true) ?: [];
        $log[] = [ 'time' => current_time('mysql'), 'msg' => "Email sent: $subject" ];
        update_post_meta($lead_id, '_saas_lead_log', array_slice($log, -10));

        wp_send_json_success( 'Email sent successfully to ' . $lead_email );
    }

    wp_send_json_error( 'Failed to send email' );
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
    $existing = get_posts(['post_type' => 'saas_profile', 'author' => $user_id, 'numberposts' => -1, 'post_status' => 'any']);
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
            $l_id = wp_insert_post([
                'post_type'   => 'saas_link',
                'post_title'  => $l['t'],
                'post_status' => 'publish',
                'post_author' => $user_id,
                'menu_order'  => $idx
            ]);
            update_post_meta($l_id, '_saas_profile_id', $p_id); // Critical: Associate with profile
            update_post_meta($l_id, '_saas_block_animation', 'fadeinup');
            update_post_meta($l_id, '_saas_link_url', $l['u']);
            update_post_meta($l_id, '_saas_block_type', $l['type']);
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

// 16. AJAX: Bulk Delete Leads
add_action( 'wp_ajax_saas_bulk_delete_leads', 'saas_ajax_bulk_delete_leads' );
function saas_ajax_bulk_delete_leads() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $ids = isset( $_POST['lead_ids'] ) ? (array) $_POST['lead_ids'] : [];
    if ( empty( $ids ) ) wp_send_json_error( 'No leads selected' );

    $count = 0;
    foreach ( $ids as $id ) {
        $post = get_post( $id );
        if ( $post && $post->post_type === 'saas_lead' && $post->post_author == get_current_user_id() ) {
            wp_delete_post( $id, true );
            $count++;
        }
    }

    wp_send_json_success( "$count leads deleted" );
}

// 17. AJAX: Check Integration Connection
add_action( 'wp_ajax_saas_check_integration', 'saas_ajax_check_integration' );
function saas_ajax_check_integration() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $platform = sanitize_text_field( $_POST['platform'] );
    // In this elite prototype, we simulate a successful connection check
    // if the user has provided any value in the dashboard fields.

    wp_send_json_success( "Connection to " . ucfirst($platform) . " verified! leads will sync automatically. 🚀" );
}

// 24. AJAX: Check Username Availability
add_action( 'wp_ajax_saas_check_username', 'saas_ajax_check_username' );
add_action( 'wp_ajax_nopriv_saas_check_username', 'saas_ajax_check_username' );
function saas_ajax_check_username() {
    $username = sanitize_user( $_POST['username'] );
    if ( empty($username) ) wp_send_json_error('Empty');

    // Check WordPress users
    if ( username_exists($username) ) wp_send_json_error('Taken');

    // Check SaaS Profile slugs
    $exists = get_posts([
        'name' => $username,
        'post_type' => 'saas_profile',
        'post_status' => 'publish',
        'fields' => 'ids',
        'numberposts' => 1
    ]);

    if ( ! empty($exists) ) wp_send_json_error('Taken');

    wp_send_json_success('Available');
}

// 21. AJAX: Simulate Pro Upgrade
add_action( 'wp_ajax_saas_simulate_pro_upgrade', 'saas_ajax_simulate_pro_upgrade' );
function saas_ajax_simulate_pro_upgrade() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );
    $user_id = get_current_user_id();

    // In this simulation, we grant the pro subscription plan
    update_user_meta($user_id, '_saas_subscription_plan', 'pro');
    update_user_meta($user_id, '_saas_subscription_expiry', strtotime('+1 year'));

    // Optionally create a mock license post
    wp_insert_post([
        'post_type'   => 'saas_license',
        'post_title'  => 'Simulated Pro License for ' . wp_get_current_user()->display_name,
        'post_status' => 'publish',
        'post_author' => $user_id,
    ]);

    wp_send_json_success('Successfully upgraded to Pro! Welcome to the Elite club. 🚀');
}

// 20. AJAX: Apply Coupon
add_action( 'wp_ajax_saas_apply_coupon', 'saas_ajax_apply_coupon' );
function saas_ajax_apply_coupon() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );
    $code = strtoupper(sanitize_text_field($_POST['coupon']));

    // Check Affiliate Coupons first
    $aff_coupons = get_option('saas_affiliate_coupons') ?: [];
    foreach ($aff_coupons as $c) {
        if (strtoupper($c['code']) === $code) {
            $discount = floatval($c['discount']);
            wp_send_json_success("Affiliate coupon applied! You get $discount% off.");
        }
    }

    // Standard coupons
    $valid_coupons = ['ELITE20' => 20, 'SAASLAUNCH' => 50];

    if (isset($valid_coupons[$code])) {
        $discount = $valid_coupons[$code];
        wp_send_json_success("Coupon applied! You get $discount% off.");
    } else {
        wp_send_json_error("Invalid or expired coupon code.");
    }
}

// 22. AJAX: Clone/Duplicate Block
add_action( 'wp_ajax_saas_clone_link', 'saas_ajax_clone_link' );
function saas_ajax_clone_link() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );
    $link_id = intval( $_POST['link_id'] );
    $post = get_post( $link_id );
    if ( ! $post || $post->post_author != get_current_user_id() ) wp_send_json_error( 'Unauthorized' );

    $new_id = wp_insert_post([
        'post_type'   => 'saas_link',
        'post_title'  => $post->post_title . ' (Copy)',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'menu_order'  => $post->menu_order + 1
    ]);

    if ( ! is_wp_error($new_id) ) {
        $meta = get_post_custom($link_id);
        foreach ($meta as $k => $v) {
            foreach ($v as $val) update_post_meta($new_id, $k, maybe_unserialize($val));
        }
        wp_send_json_success( 'Block duplicated' );
    }
    wp_send_json_error( 'Failed to duplicate' );
}

// 25. AJAX: AI Profile Assistant
add_action( 'wp_ajax_saas_ai_assist', 'saas_ajax_ai_assist' );
function saas_ajax_ai_assist() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $target = sanitize_text_field( $_POST['target'] );
    $niche  = sanitize_text_field( $_POST['niche'] );

    $suggestions = [
        'coach' => [
            'headline' => [
                "Helping Founders Scale from 6 to 7 Figures 🚀",
                "Unlock Your Peak Performance with Elite Systems",
                "Strategic Advisory for High-Impact Entrepreneurs"
            ],
            'bio' => [
                "Ex-SaaS Founder turned Performance Coach. I help seed-stage startups optimize their unit economics and reduce churn through proven psychological frameworks.",
                "Helping you reclaim 20+ hours a week while doubling your revenue. Certified high-performance coach for busy CEOs."
            ]
        ],
        'servant' => [
            'headline' => [
                "Dedicated to Progress & Community Service 🏛️",
                "Building a Brighter Future for Our District",
                "Transparency. Integrity. Public Service."
            ],
            'bio' => [
                "Serving as your advocate in public office. I am dedicated to sustainable growth, educational excellence, and fiscal responsibility for our community.",
                "Advancing policies that empower local families and small businesses. Together, we are building a more resilient and inclusive city."
            ]
        ],
        'speaker' => [
            'headline' => [
                "Inspiring Transformation through High-Impact Keynotes 🎙️",
                "Empowering Teams to Lead with Purpose",
                "Global Keynote Speaker & Thought Leader"
            ],
            'bio' => [
                "Helping organizations navigate change and build resilient cultures. I share actionable insights on leadership, innovation, and peak performance.",
                "Captivating audiences worldwide with stories of grit and growth. I help leaders bridge the gap between vision and execution."
            ]
        ],
        'author' => [
            'headline' => [
                "Exploring the Intersection of Tech & Humanity ✍️",
                "Bestselling Author of 'The Elite mindset'",
                "Storyteller. Researcher. Writer."
            ],
            'bio' => [
                "Writing at the frontiers of personal growth and digital culture. My work helps modern professionals build meaningful lives in an era of distraction.",
                "Crafting narratives that challenge the status quo. Join me as I explore the deep questions that define our shared future."
            ]
        ],
        'lawyer' => [
            'headline' => [
                "Strategic Legal Advocacy for Elite Clients ⚖️",
                "Protecting Your Interests. Defending Your Future.",
                "High-Stakes Litigation & Advisory"
            ],
            'bio' => [
                "Providing expert legal counsel with a focus on results. I help businesses and individuals navigate complex legal landscapes with confidence and precision.",
                "Dedicated to excellence in legal practice. My mission is to provide sophisticated representation that honors your unique goals."
            ]
        ],
        'doctor' => [
            'headline' => [
                "Compassionate Care, Precision Medicine 🩺",
                "Your Partner in Health & Longevity",
                "Evidence-Based Wellness for Modern Lives"
            ],
            'bio' => [
                "Leading with science and heart. I specialize in personalized healthcare strategies that empower you to thrive at every stage of life.",
                "Advancing the future of medicine through innovation and patient-centered care. Dedicated to your well-being and peak vitality."
            ]
        ],
        'artist' => [
            'headline' => [
                "Visual Storytelling through Digital Art 🎨",
                "Capturing the Essence of Modern Brands",
                "Design that Inspires. Art that Connects."
            ],
            'bio' => [
                "Independent designer creating immersive visual experiences. I help forward-thinking brands stand out through artistic excellence and strategic design.",
                "Exploring the boundaries of digital creativity. My work focuses on the intersection of aesthetic beauty and functional impact."
            ]
        ],
        'agency' => [
            'headline' => [
                "Scaling Brands through Performance Marketing 🏢",
                "Your Growth Partner for the Digital Era",
                "Bespoke Agency Solutions for Global Leaders"
            ],
            'bio' => [
                "We build high-performance funnels that drive revenue. Our agency specializes in turning cold traffic into loyal brand advocates for elite founders.",
                "Mastering the art of digital acquisition. We provide the strategy and execution your brand needs to dominate its niche."
            ]
        ],
        'creator' => [
            'headline' => [
                "Exclusive Insights & Behind-the-Scenes 🎥",
                "Building the Next Generation of Digital Brands",
                "Sharing My Journey to 1M Followers"
            ],
            'bio' => [
                "Daily tips on content strategy, community building, and monetization. Join 50k+ other creators on the journey to creative independence.",
                "Documenting the process of building a multi-channel media empire. I share what works (and what doesn't) in the attention economy."
            ]
        ],
        'realtor' => [
            'headline' => [
                "Luxury Living in the Heart of the City 🏡",
                "Your Gateway to Off-Market Luxury Listings",
                "Modern Homes for Modern Families"
            ],
            'bio' => [
                "Top 1% Global Agent specializing in luxury residential properties. I help discerning buyers find their dream home through data-driven advisory.",
                "Specializing in bespoke real estate investment. Helping you build a legacy through high-yield property acquisition."
            ]
        ],
        'business' => [
            'headline' => [
                "Driving Results through Strategic Design 📈",
                "Scaling Brands with Innovative Tech Solutions",
                "Your Partner in Digital Transformation"
            ],
            'bio' => [
                "Streamlining operations for modern enterprises. We provide the infrastructure you need to scale globally with confidence.",
                "Innovation-first consultancy helping legacy brands transition to the digital-first economy. We build the future of commerce."
            ]
        ]
    ];

    $niche_data = $suggestions[$niche] ?? $suggestions['business'];
    $list = $niche_data[$target] ?? $niche_data['headline'];
    $suggestion = $list[array_rand($list)];

    wp_send_json_success( $suggestion );
}

// 18. AJAX: Clone Profile
add_action( 'wp_ajax_saas_clone_profile', 'saas_ajax_clone_profile' );
function saas_ajax_clone_profile() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );
    $user_id = get_current_user_id();
    $profile_id = intval( $_POST['profile_id'] );

    $profile = get_post( $profile_id );
    if ( ! $profile || $profile->post_author != $user_id ) wp_send_json_error( 'Unauthorized' );

    // 1. Limit Check
    $payments = new Saas_Payments();
    $existing = get_posts(['post_type' => 'saas_profile', 'author' => $user_id, 'numberposts' => -1, 'post_status' => 'any']);
    if ( count($existing) >= 1 && !$payments->is_pro_user($user_id) ) {
        wp_send_json_error( 'Free users are limited to 1 profile. Upgrade to Pro to clone.' );
    }

    // 2. Clone Profile Post
    $new_profile_id = wp_insert_post([
        'post_type'   => 'saas_profile',
        'post_title'  => $profile->post_title . ' (Copy)',
        'post_status' => 'publish',
        'post_author' => $user_id,
    ]);

    if ( is_wp_error($new_profile_id) ) wp_send_json_error( 'Clone failed' );

    // 3. Copy Meta
    $meta_keys = [
        '_saas_bio', '_saas_headline', '_saas_theme_color', '_saas_bg_type',
        '_saas_bg_color', '_saas_bg_gradient', '_saas_btn_shape', '_saas_font_family',
        '_saas_container_shadow', '_saas_profile_theme', '_saas_seo_title', '_saas_seo_desc'
    ];
    foreach ($meta_keys as $key) {
        update_post_meta($new_profile_id, $key, get_post_meta($profile_id, $key, true));
    }

    // 4. Clone Associated Links
    $links = get_posts([
        'post_type'  => 'saas_link',
        'author'     => $user_id,
        'meta_query' => [['key' => '_saas_profile_id', 'value' => $profile_id]],
        'numberposts' => -1
    ]);

    foreach ($links as $l) {
        $new_link_id = wp_insert_post([
            'post_type'   => 'saas_link',
            'post_title'  => $l->post_title,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        // Copy all meta for the link
        $link_meta = get_post_custom($l->ID);
        foreach ($link_meta as $key => $values) {
            foreach ($values as $value) {
                update_post_meta($new_link_id, $key, maybe_unserialize($value));
            }
        }
        // Update to point to the NEW profile
        update_post_meta($new_link_id, '_saas_profile_id', $new_profile_id);
    }

    wp_send_json_success([ 'id' => $new_profile_id ]);
}

// 26. AJAX: Delete Profile
add_action( 'wp_ajax_saas_delete_profile', 'saas_ajax_delete_profile' );
function saas_ajax_delete_profile() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );
    $user_id = get_current_user_id();
    $profile_id = intval( $_POST['profile_id'] );

    $profile = get_post( $profile_id );
    if ( ! $profile || $profile->post_author != $user_id ) wp_send_json_error( 'Unauthorized' );

    // 1. Prevent deleting the only profile
    $existing = get_posts(['post_type' => 'saas_profile', 'author' => $user_id, 'fields' => 'ids', 'numberposts' => -1, 'post_status' => 'any']);
    if ( count($existing) <= 1 ) {
        wp_send_json_error( 'You must have at least one profile. Create a new one before deleting this one.' );
    }

    // 2. Delete Profile and associated links
    $links = get_posts([
        'post_type'  => 'saas_link',
        'author'     => $user_id,
        'meta_key'   => '_saas_profile_id',
        'meta_value' => $profile_id,
        'numberposts' => -1,
        'fields' => 'ids'
    ]);
    foreach ($links as $l_id) wp_delete_post($l_id, true);

    wp_delete_post($profile_id, true);

    wp_send_json_success( 'Profile and its blocks deleted successfully.' );
}
