<?php
/**
 * License Management Engine
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_License_Manager {
    public function __construct() {
        add_action( 'wp_ajax_saas_generate_license', [ $this, 'ajax_generate_license' ] );
        add_action( 'wp_ajax_saas_validate_license', [ $this, 'ajax_validate_license' ] );
    }

    /**
     * Generate a new unique license key
     */
    public function generate_key() {
        return strtoupper( 'ELITE-' . bin2hex( random_bytes(4) ) . '-' . bin2hex( random_bytes(4) ) );
    }

    /**
     * AJAX: Create a new license for a user (Admin only)
     */
    public function ajax_generate_license() {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        check_ajax_referer( 'saas_dashboard_nonce', 'security' );

        $user_id = intval( $_POST['user_id'] );
        $plan    = sanitize_text_field( $_POST['plan'] );
        $expiry  = sanitize_text_field( $_POST['expiry'] ); // Date string

        $key = $this->generate_key();

        $license_id = wp_insert_post([
            'post_type'   => 'saas_license',
            'post_title'  => $key,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        update_post_meta( $license_id, '_saas_license_plan', $plan );
        update_post_meta( $license_id, '_saas_license_expiry', $expiry ? strtotime($expiry) : 0 );
        update_post_meta( $license_id, '_saas_license_status', 'active' );

        wp_send_json_success([ 'key' => $key, 'license_id' => $license_id ]);
    }

    /**
     * AJAX: Validate and activate a license key for current user
     */
    public function ajax_validate_license() {
        check_ajax_referer( 'saas_dashboard_nonce', 'security' );
        $key = strtoupper( sanitize_text_field( $_POST['license_key'] ) );
        $user_id = get_current_user_id();

        $licenses = get_posts([
            'post_type'   => 'saas_license',
            'title'       => $key,
            'post_status' => 'publish',
            'numberposts' => 1
        ]);

        if ( empty( $licenses ) ) {
            wp_send_json_error( 'Invalid license key.' );
        }

        $license_id = $licenses[0]->ID;
        $status = get_post_meta( $license_id, '_saas_license_status', true );
        $expiry = get_post_meta( $license_id, '_saas_license_expiry', true );

        if ( $status !== 'active' ) {
            wp_send_json_error( 'This license is no longer active.' );
        }

        if ( $expiry && $expiry < time() ) {
            update_post_meta( $license_id, '_saas_license_status', 'expired' );
            wp_send_json_error( 'This license has expired.' );
        }

        // Activate for user
        $plan = get_post_meta( $license_id, '_saas_license_plan', true ) ?: 'pro';
        update_user_meta( $user_id, '_saas_subscription_plan', $plan );
        update_user_meta( $user_id, '_saas_subscription_expiry', $expiry );
        update_post_meta( $license_id, '_saas_activated_user', $user_id );
        update_post_meta( $license_id, '_saas_license_status', 'used' );

        wp_send_json_success( 'License activated! Your account is now ' . strtoupper($plan) );
    }
}
new Saas_License_Manager();
