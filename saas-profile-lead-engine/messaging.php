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
        add_action( 'wp_ajax_saas_send_broadcast', [ $this, 'handle_send_broadcast' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_admin_meta_boxes' ] );
    }

    public function register_message_cpt() {
        // CPTs now handled centrally in post-types.php
    }

    public function handle_send_message() {
        check_ajax_referer( 'saas_dashboard_nonce', 'security' );

        $to_user_id = intval( $_POST['to_user'] );
        $content = sanitize_textarea_field( $_POST['message'] );
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : ('Message from ' . wp_get_current_user()->display_name);

        $msg_id = wp_insert_post([
            'post_type' => 'saas_message',
            'post_title' => $subject,
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

    public function add_admin_meta_boxes() {
        add_meta_box( 'saas_msg_details', 'Message Details', [ $this, 'render_admin_meta_box' ], 'saas_message', 'normal', 'high' );
    }

    public function handle_send_broadcast() {
        if(!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('saas_dashboard_nonce', 'security');

        $content = sanitize_textarea_field($_POST['message']);
        $subject = sanitize_text_field($_POST['subject']);
        $users = get_users(['fields' => 'ID']);

        foreach($users as $u_id) {
            $msg_id = wp_insert_post([
                'post_type' => 'saas_message',
                'post_title' => $subject . ' [BROADCAST]',
                'post_content' => $content,
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
            ]);
            update_post_meta($msg_id, '_saas_msg_recipient', $u_id);
            update_post_meta($msg_id, '_saas_msg_status', 'unread');
            update_post_meta($msg_id, '_saas_is_broadcast', 1);
        }

        wp_send_json_success('Broadcast sent to ' . count($users) . ' users!');
    }

    public function render_admin_meta_box( $post ) {
        $recipient_id = get_post_meta($post->ID, '_saas_msg_recipient', true);
        $recipient = get_userdata($recipient_id);
        $sender = get_userdata($post->post_author);
        ?>
        <div class="saas-admin-msg-info">
            <p><strong>From:</strong> <?php echo $sender ? $sender->display_name . ' (ID: '.$sender->ID.')' : 'Unknown'; ?></p>
            <p><strong>To:</strong> <?php echo $recipient ? $recipient->display_name . ' (ID: '.$recipient->ID.')' : 'Unknown'; ?></p>
            <p><strong>Status:</strong> <?php echo get_post_meta($post->ID, '_saas_msg_status', true); ?></p>
        </div>
        <hr>
        <h4>Quick Reply</h4>
        <p>Send a reply back to the sender of this message.</p>
        <form action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST" id="saas-admin-reply-form">
            <input type="hidden" name="action" value="saas_send_message">
            <input type="hidden" name="security" value="<?php echo wp_create_nonce('saas_dashboard_nonce'); ?>">
            <input type="hidden" name="to_user" value="<?php echo $post->post_author; ?>">
            <textarea name="message" rows="5" style="width:100%;" required placeholder="Type your reply here..."></textarea>
            <p><button type="submit" class="button button-primary">Send Reply</button></p>
        </form>
        <script>
            jQuery('#saas-admin-reply-form').on('submit', function(e) {
                e.preventDefault();
                var $form = jQuery(this);
                $form.find('button').prop('disabled', true).text('Sending...');
                jQuery.post(ajaxurl, $form.serialize(), function(res) {
                    if(res.success) {
                        alert('Reply sent!');
                        $form.find('textarea').val('');
                    } else {
                        alert('Error: ' + res.data);
                    }
                    $form.find('button').prop('disabled', false).text('Send Reply');
                });
            });
        </script>
        <?php
    }
}
new Saas_Messaging();
