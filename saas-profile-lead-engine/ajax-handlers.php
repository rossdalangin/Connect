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
        update_post_meta( $link_id, '_saas_link_url', $url );
        update_post_meta( $link_id, '_saas_priority', 0 );

        // Extended meta for complex blocks
        if ($type === 'testimonial' && isset($_POST['extra'])) {
            update_post_meta($link_id, '_saas_testimonial_text', sanitize_textarea_field($_POST['extra']));
        } elseif ($type === 'faq' && isset($_POST['extra'])) {
            update_post_meta($link_id, '_saas_faq_answer', sanitize_textarea_field($_POST['extra']));
        } elseif ($type === 'pricing' && isset($_POST['extra'])) {
            update_post_meta($link_id, '_saas_price', sanitize_text_field($_POST['extra']));
            // Sample feature list for pricing
            update_post_meta($link_id, '_saas_features', ['Premium Support', 'Unlimited Links', 'No Branding']);
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
        'bio'         => $_POST['bio'],
        'headline'    => $_POST['headline'],
        'theme_color' => $_POST['theme_color'],
    ];

    saas_update_profile_meta( $profile_id, $data );

    // Additional profile meta
    update_post_meta($profile_id, '_saas_bg_type', sanitize_text_field($_POST['bg_type']));
    update_post_meta($profile_id, '_saas_bg_color', sanitize_text_field($_POST['bg_value']));
    if ($_POST['bg_type'] === 'gradient') {
        update_post_meta($profile_id, '_saas_bg_gradient', sanitize_text_field($_POST['bg_value']));
    }

    wp_send_json_success( 'Profile saved' );
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

    // Determine meta key based on type
    $type = get_post_meta( $link_id, '_saas_block_type', true );
    if ($type === 'testimonial') update_post_meta($link_id, '_saas_testimonial_text', $extra);
    elseif ($type === 'faq') update_post_meta($link_id, '_saas_faq_answer', $extra);
    elseif ($type === 'pricing') update_post_meta($link_id, '_saas_price', $extra);
    elseif ($type === 'countdown') update_post_meta($link_id, '_saas_expiry', $extra);
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

    // 1. Delete existing blocks
    $old_blocks = get_posts(['post_type' => 'saas_link', 'author' => $user_id, 'numberposts' => -1]);
    foreach ($old_blocks as $ob) wp_delete_post($ob->ID, true);

    // 2. Define Template Sets
    $sets = [
        'coach' => [
            ['title' => 'Watch Intro Video', 'url' => 'https://youtube.com', 'type' => 'video'],
            ['title' => 'Apply for Coaching', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
            ['title' => 'Client Success', 'url' => '#', 'type' => 'testimonial', 'extra' => 'Alex helped me double my revenue!'],
        ],
        'freelancer' => [
            ['title' => 'My Portfolio', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/300\nhttps://via.placeholder.com/301"],
            ['title' => 'Hire Me', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
        ]
    ];

    if ( isset($sets[$template]) ) {
        foreach ( $sets[$template] as $index => $b ) {
            $link_id = wp_insert_post(['post_type' => 'saas_link', 'post_title' => $b['title'], 'post_status' => 'publish', 'post_author' => $user_id]);
            update_post_meta($link_id, '_saas_block_type', $b['type']);
            update_post_meta($link_id, '_saas_link_url', $b['url']);
            update_post_meta($link_id, '_saas_priority', $index);
            if (isset($b['style'])) update_post_meta($link_id, '_saas_block_style', $b['style']);
            if (isset($b['extra'])) {
                if ($b['type'] === 'testimonial') update_post_meta($link_id, '_saas_testimonial_text', $b['extra']);
                if ($b['type'] === 'image_gallery') update_post_meta($link_id, '_saas_gallery_images', explode("\n", $b['extra']));
            }
        }
        wp_send_json_success('Template applied');
    }

    wp_send_json_error('Invalid template');
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
    fputcsv($output, ['Name', 'Email', 'Date', 'Source Profile ID']);

    foreach ($leads as $lead) {
        fputcsv($output, [
            get_post_meta($lead->ID, '_saas_lead_name', true),
            get_post_meta($lead->ID, '_saas_lead_email', true),
            get_the_date('Y-m-d H:i', $lead->ID),
            get_post_meta($lead->ID, '_saas_lead_source_id', true),
        ]);
    }
    fclose($output);
    exit;
}
