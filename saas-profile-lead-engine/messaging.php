<?php
/**
 * Internal Messaging System
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Messaging {
    public function __construct() {
        add_action( 'init', [ $this, 'register_message_cpt' ] );
        add_action( 'wp_ajax_saas_send_message', [ $this, 'handle_send_message' ] );
        add_action( 'wp_ajax_saas_get_message_content', [ $this, 'get_message_content' ] );
    }

    public function register_message_cpt() {
        register_post_type( 'saas_message', [
            'labels' => ['name' => 'Messages', 'singular_name' => 'Message'],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-email-alt',
            'supports' => ['title', 'editor', 'author'],
        ]);
    }

    public function handle_send_message() {
        check_ajax_referer( 'saas_dashboard_nonce', 'security' );

        $to_user_id = intval( $_POST['to_user'] );
        $content = sanitize_textarea_field( $_POST['message'] );

        $msg_id = wp_insert_post([
            'post_type' => 'saas_message',
            'post_title' => 'Message from ' . wp_get_current_user()->display_name,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);

        update_post_meta( $msg_id, '_saas_msg_recipient', $to_user_id );
        update_post_meta( $msg_id, '_saas_msg_status', 'unread' );

        wp_send_json_success( 'Message sent successfully' );
    }

    public function get_message_content() {
        check_ajax_referer( 'saas_dashboard_nonce', 'security' );
        $msg_id = intval( $_POST['msg_id'] );
        $msg = get_post( $msg_id );

        if ( ! $msg || ( $msg->post_author != get_current_user_id() && get_post_meta($msg_id, '_saas_msg_recipient', true) != get_current_user_id() ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        // Mark as read if recipient opens it
        if ( get_post_meta($msg_id, '_saas_msg_recipient', true) == get_current_user_id() ) {
            update_post_meta($msg_id, '_saas_msg_status', 'read');
        }

        wp_send_json_success([
            'title' => $msg->post_title,
            'content' => nl2br($msg->post_content),
            'from_id' => $msg->post_author
        ]);
    }
}
new Saas_Messaging();
