<?php
/**
 * Lead Generation Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Handle Frontend Lead Form Submission
add_action( 'wp_ajax_saas_submit_lead', 'saas_ajax_submit_lead' );
add_action( 'wp_ajax_nopriv_saas_submit_lead', 'saas_ajax_submit_lead' );

function saas_ajax_submit_lead() {
    check_ajax_referer( 'saas_lead_nonce', 'security' );

    // 0. Rate Limiting (Spam Prevention)
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'saas_lead_limit_' . md5($ip);
    $attempts = get_transient($transient_key) ?: 0;

    if ($attempts >= 3) {
        wp_send_json_error( 'Too many requests. Please try again in 10 minutes.' );
    }

    $profile_id = intval( $_POST['profile_id'] );
    $name       = sanitize_text_field( $_POST['name'] );
    $email      = sanitize_email( $_POST['email'] );

    // 1. Simple Honeypot Check (Spam Protection)
    if ( ! empty( $_POST['saas_honeypot'] ) ) {
        wp_send_json_error( 'Spam detected' );
    }

    // 2. Mock reCAPTCHA verification stub
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    // if ( $recaptcha_response && ! saas_verify_recaptcha($recaptcha_response) ) {
    //     wp_send_json_error( 'Verification failed' );
    // }
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
        if (isset($_POST['phone'])) update_post_meta($lead_id, '_saas_lead_phone', sanitize_text_field($_POST['phone']));
        if (isset($_POST['message'])) update_post_meta($lead_id, '_saas_lead_message', sanitize_textarea_field($_POST['message']));
        if (isset($_POST['block_id'])) update_post_meta($lead_id, '_saas_lead_block_id', intval($_POST['block_id']));
        update_post_meta( $lead_id, '_saas_lead_source_id', $profile_id );

        // Basic Tagging System
        $tags = get_post_meta( $profile_id, '_saas_lead_tags', true ) ?: [ 'New' ];
        update_post_meta( $lead_id, '_saas_lead_tags', $tags );

        // 3. Track Conversion Event
        $analytics = new Saas_Analytics();
        $analytics->record_event($owner_id, 'lead_conversion', $profile_id);

        // Automation Hooks
        $redirect_url = get_post_meta( $profile_id, '_saas_lead_redirect', true );
        $webhook_url  = get_post_meta( $profile_id, '_saas_lead_webhook', true );

        if ( $webhook_url ) {
            wp_remote_post( $webhook_url, [
                'body' => [ 'name' => $name, 'email' => $email, 'profile' => $profile_id ]
            ]);
        }

        // Elite Integration: Mailchimp
        $mc_api = get_post_meta($profile_id, '_saas_mailchimp_api', true);
        $mc_list = get_post_meta($profile_id, '_saas_mailchimp_list', true);
        if ($mc_api && $mc_list) {
            // Mock Mailchimp API call
            error_log("SaaS Log: Syncing lead to Mailchimp List $mc_list");
        }

        // Elite Integration: HubSpot
        $hs_token = get_post_meta($profile_id, '_saas_hubspot_token', true);
        if ($hs_token) {
            // Mock HubSpot API call
            error_log("SaaS Log: Syncing lead to HubSpot CRM");
        }

        // Email Notification
        $owner_email = get_the_author_meta('user_email', $owner_id);
        $subject = "🚀 New Lead Captured: $name";

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        $body = "
            <div style='font-family:sans-serif; max-width:600px; padding:20px; border:1px solid #eee; border-radius:12px;'>
                <h2 style='color:#6c5ce7;'>You've got a new lead!</h2>
                <p>A new visitor just submitted a form on your SaaS profile.</p>
                <hr style='border:0; border-top:1px solid #eee;'>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> <a href='mailto:$email'>$email</a></p>
                <p><strong>Captured via:</strong> " . get_the_title($profile_id) . "</p>
                <hr style='border:0; border-top:1px solid #eee;'>
                <p><a href='" . home_url('/dashboard') . "' style='background:#6c5ce7; color:#fff; padding:10px 20px; text-decoration:none; border-radius:6px; display:inline-block;'>View in Dashboard</a></p>
                <p style='font-size:0.8rem; color:#999; margin-top:30px;'>Sent automatically by your SaaS platform.</p>
            </div>
        ";
        wp_mail($owner_email, $subject, $body, $headers);

        // Elite Pro Auto-responder to Lead
        $auto_respond = get_post_meta($profile_id, '_saas_lead_auto_respond', true);
        if ($auto_respond) {
            $auto_msg = get_post_meta($profile_id, '_saas_lead_auto_msg', true);
            if ($auto_msg) {
                $owner_name = get_the_title($profile_id);
                $resp_subject = "RE: Your inquiry to $owner_name";
                $resp_body = "
                    <div style='font-family:sans-serif; max-width:600px; padding:20px; border-radius:12px; border:1px solid #eee;'>
                        <p>Hi $name,</p>
                        " . wpautop($auto_msg) . "
                        <hr style='border:0; border-top:1px solid #eee; margin:20px 0;'>
                        <p><small>Sent via $owner_name's digital profile.</small></p>
                    </div>
                ";
                wp_mail($email, $resp_subject, $resp_body, $headers);
            }
        }

        // Lead Magnet Delivery (Simulated)
        $lead_magnet_url = get_post_meta( $profile_id, '_saas_lead_magnet_url', true );

        // Increment rate limit attempts
        set_transient($transient_key, $attempts + 1, 600); // 10 minutes

        wp_send_json_success([
            'message'  => 'Thank you! We will contact you soon.',
            'redirect' => $redirect_url ? esc_url($redirect_url) : '',
            'download' => $lead_magnet_url ? esc_url($lead_magnet_url) : ''
        ]);
    } else {
        wp_send_json_error( 'Failed to save lead' );
    }
}
