<?php
/**
 * Lead Generation Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Handle Frontend Lead Form Submission
add_action( 'wp_ajax_saas_submit_lead', 'saas_ajax_submit_lead' );
add_action( 'wp_ajax_nopriv_saas_submit_lead', 'saas_ajax_submit_lead' );

function saas_ajax_submit_lead() {
    $profile_id = intval( $_POST['profile_id'] );
    $name       = sanitize_text_field( $_POST['name'] );
    $email      = sanitize_email( $_POST['email'] );
    $owner_id   = get_post_field( 'post_author', $profile_id );

    if ( ! $profile_id || ! is_email( $email ) ) {
        wp_send_json_error( 'Invalid data' );
    }

    $lead_id = wp_insert_post([
        'post_type'   => 'saas_lead',
        'post_title'  => "New Lead: $name ($email)",
        'post_status' => 'publish',
        'post_author' => $owner_id,
    ]);

    if ( ! is_wp_error( $lead_id ) ) {
        update_post_meta( $lead_id, '_saas_lead_name', $name );
        update_post_meta( $lead_id, '_saas_lead_email', $email );
        update_post_meta( $lead_id, '_saas_lead_source_id', $profile_id );

        // Automation Hooks
        $redirect_url = get_post_meta( $profile_id, '_saas_lead_redirect', true );
        $webhook_url  = get_post_meta( $profile_id, '_saas_lead_webhook', true );

        if ( $webhook_url ) {
            wp_remote_post( $webhook_url, [
                'body' => [ 'name' => $name, 'email' => $email, 'profile' => $profile_id ]
            ]);
        }

        wp_send_json_success([
            'message'  => 'Thank you! We will contact you soon.',
            'redirect' => $redirect_url ? esc_url($redirect_url) : ''
        ]);
    } else {
        wp_send_json_error( 'Failed to save lead' );
    }
}
