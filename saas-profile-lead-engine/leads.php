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

        // Priority: Block-level redirect
        if (isset($_POST['block_id'])) {
            $block_redirect = get_post_meta(intval($_POST['block_id']), '_saas_link_url', true);
            if ($block_redirect && $block_redirect !== '#') {
                $redirect_url = $block_redirect;
            }
        }

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
            $dc = substr($mc_api, strpos($mc_api, '-') + 1);
            wp_remote_post("https://{$dc}.api.mailchimp.com/3.0/lists/{$mc_list}/members", [
                'headers' => [ 'Authorization' => 'apikey ' . $mc_api, 'Content-Type' => 'application/json' ],
                'body' => json_encode([ 'email_address' => $email, 'status' => 'subscribed', 'merge_fields' => ['FNAME' => $name] ])
            ]);
        }

        // Elite Integration: HubSpot
        $hs_token = get_post_meta($profile_id, '_saas_hubspot_token', true);
        if ($hs_token) {
            wp_remote_post("https://api.hubapi.com/crm/v3/objects/contacts", [
                'headers' => [ 'Authorization' => 'Bearer ' . $hs_token, 'Content-Type' => 'application/json' ],
                'body' => json_encode([ 'properties' => [ 'email' => $email, 'firstname' => $name ] ])
            ]);
        }

        // 4. Send Emails via Dynamic Templates
        $templates = get_option('saas_email_templates');
        $replacements = [
            '{name}'          => $name,
            '{email}'         => $email,
            '{profile_title}' => get_the_title($profile_id),
            '{dashboard_url}' => home_url('/dashboard'),
            '{site_name}'     => get_bloginfo('name')
        ];

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        // A. Admin/Owner Notification
        $owner_email = get_the_author_meta('user_email', $owner_id);
        $tpl_admin   = $templates['new_lead_admin'] ?? [
            'subject' => '🚀 New Lead Captured: {name}',
            'body'    => "<h2>You've got a new lead!</h2><p><strong>Name:</strong> {name}<br><strong>Email:</strong> {email}<br><strong>Source:</strong> {profile_title}</p><p><a href='{dashboard_url}'>View in Dashboard</a></p>"
        ];

        $admin_subject = str_replace(array_keys($replacements), array_values($replacements), $tpl_admin['subject']);
        $admin_body    = str_replace(array_keys($replacements), array_values($replacements), $tpl_admin['body']);
        wp_mail($owner_email, $admin_subject, $admin_body, $headers);

        // B. Elite Pro Auto-responder to Lead
        $auto_respond = get_post_meta($profile_id, '_saas_lead_auto_respond', true);
        if ($auto_respond) {
            $tpl_lead = $templates['lead_autoresponder'] ?? [
                'subject' => 'Re: Your inquiry to {profile_title}',
                'body'    => "Hi {name},<br><br>Thank you for reaching out! I've received your inquiry and will get back to you shortly.<br><br>Best,<br>{profile_title}"
            ];

            $lead_subject = str_replace(array_keys($replacements), array_values($replacements), $tpl_lead['subject']);
            $lead_body    = str_replace(array_keys($replacements), array_values($replacements), $tpl_lead['body']);

            // Allow per-profile custom auto-msg if set, otherwise use global template
            $custom_msg = get_post_meta($profile_id, '_saas_lead_auto_msg', true);
            if ($custom_msg) {
                $lead_body = "Hi $name,<br><br>" . wpautop($custom_msg) . "<br><br>Best,<br>" . get_the_title($profile_id);
            }

            wp_mail($email, $lead_subject, $lead_body, $headers);
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
