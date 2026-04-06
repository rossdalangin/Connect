<?php
/**
 * Dashboard AJAX Handlers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. AJAX: Update Link Order
add_action( 'wp_ajax_saas_update_link_order', 'saas_ajax_update_link_order' );
function saas_ajax_update_link_order() {
    check_ajax_referer( 'saas_dashboard_nonce', 'security' );

    $link_ids = isset( $_POST['link_ids'] ) ? array_map( 'intval', $_POST['link_ids'] ) : [];

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
        update_post_meta( $link_id, '_saas_link_url', $url );
        update_post_meta( $link_id, '_saas_priority', 0 );
        wp_send_json_success([ 'id' => $link_id, 'title' => $title, 'url' => $url, 'type' => $type ]);
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

    wp_send_json_success( 'Profile saved' );
}
