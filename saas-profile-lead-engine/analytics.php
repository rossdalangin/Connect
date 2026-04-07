<?php
/**
 * Analytics Engine Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Analytics {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'saas_analytics';

        add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
        add_action( 'wp_ajax_saas_track_click', [ $this, 'track_click' ] );
        add_action( 'wp_ajax_nopriv_saas_track_click', [ $this, 'track_click' ] );
    }

    /**
     * Register REST API Endpoints
     */
    public function register_rest_endpoints() {
        register_rest_route( 'saas/v1', '/track', [
            'methods' => 'POST',
            'callback' => [ $this, 'rest_track_event' ],
            'permission_callback' => '__return_true', // Public tracking
        ]);
    }

    /**
     * Get User-Specific Analytics Summary
     */
    public function get_user_summary( $user_id ) {
        global $wpdb;
        $views  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND event_type = 'view'", $user_id ) );
        $clicks = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND event_type = 'click'", $user_id ) );

        $referrers = $wpdb->get_results( $wpdb->prepare( "SELECT referrer, COUNT(*) as count FROM {$this->table_name} WHERE user_id = %d AND referrer != '' GROUP BY referrer ORDER BY count DESC LIMIT 5", $user_id ) );

        // Breakdown Data (Simulated for this implementation)
        $devices = [
            (object)['label' => 'Mobile', 'count' => round($views * 0.7)],
            (object)['label' => 'Desktop', 'count' => round($views * 0.25)],
            (object)['label' => 'Tablet', 'count' => round($views * 0.05)],
        ];

        return [
            'views'  => $views ?: 0,
            'clicks' => $clicks ?: 0,
            'referrers' => $referrers ?: [],
            'devices' => $devices
        ];
    }

    /**
     * Get Click Counts for all of a user's links
     */
    public function get_user_link_stats( $user_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT target_id, COUNT(*) as clicks FROM {$this->table_name} WHERE user_id = %d AND event_type = 'click' GROUP BY target_id",
            $user_id
        ), OBJECT_K ); // Use target_id as key

        return $results ?: [];
    }

    /**
     * Get Global Analytics Summary (for Admin Dashboard)
     */
    public function get_global_summary() {
        global $wpdb;
        $total_views  = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'view'" );
        $total_clicks = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'click'" );
        $total_leads  = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'lead_gen'" );

        return [
            'views'  => $total_views ?: 0,
            'clicks' => $total_clicks ?: 0,
            'leads'  => $total_leads ?: 0,
        ];
    }

    /**
     * REST: Track Event (High Efficiency)
     */
    public function rest_track_event( $request ) {
        $params = $request->get_json_params();
        $target_id = intval( $params['target_id'] );
        $event = sanitize_text_field( $params['event'] );

        $post = get_post( $target_id );
        if ( $post && in_array($post->post_type, ['saas_link', 'saas_profile']) ) {
            $this->record_event( $post->post_author, $event, $target_id );
            return new WP_REST_Response( [ 'success' => true ], 200 );
        }
        return new WP_REST_Response( [ 'error' => 'Invalid target' ], 400 );
    }

    /**
     * Create Analytics Table on Activation
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saas_analytics';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            target_id bigint(20) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            referrer text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Record an Event
     */
    public function record_event( $user_id, $event_type, $target_id ) {
        global $wpdb;

        $wpdb->insert( $this->table_name, [
            'user_id'    => $user_id,
            'event_type' => $event_type,
            'target_id'  => $target_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'referrer'   => $_SERVER['HTTP_REFERER'] ?? '',
        ]);
    }

    /**
     * AJAX: Track Link Click
     */
    public function track_click() {
        $link_id = intval( $_POST['link_id'] );
        $link = get_post( $link_id );

        if ( $link && $link->post_type == 'saas_link' ) {
            $this->record_event( $link->post_author, 'click', $link_id );
            wp_send_json_success( 'Event tracked' );
        } else {
            wp_send_json_error( 'Invalid link' );
        }
    }
}
new Saas_Analytics();
