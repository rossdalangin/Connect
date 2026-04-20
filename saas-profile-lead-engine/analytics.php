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
        $leads  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND event_type = 'lead_conversion'", $user_id ) );
        $nfc    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND event_type = 'nfc_tap'", $user_id ) );

        $referrers = $wpdb->get_results( $wpdb->prepare( "SELECT referrer, COUNT(*) as count FROM {$this->table_name} WHERE user_id = %d AND referrer != '' GROUP BY referrer ORDER BY count DESC LIMIT 5", $user_id ) );

        $countries = $wpdb->get_results( $wpdb->prepare( "
            SELECT ip_address as country_code, COUNT(*) as count
            FROM {$this->table_name}
            WHERE user_id = %d AND event_type = 'view'
            GROUP BY country_code ORDER BY count DESC LIMIT 5
        ", $user_id ) );
        // Note: In production, we'd use a GeoIP library. Here we simulate country codes stored in IP column for demo.

        // Breakdown Data (Simulated for this implementation)
        $devices = [
            (object)['label' => 'Mobile', 'count' => round($views * 0.7)],
            (object)['label' => 'Desktop', 'count' => round($views * 0.25)],
            (object)['label' => 'Tablet', 'count' => round($views * 0.05)],
        ];

        return [
            'views'  => $views ?: 0,
            'clicks' => $clicks ?: 0,
            'leads'  => $leads ?: 0,
            'nfc'    => $nfc ?: 0,
            'referrers' => $referrers ?: [],
            'countries' => $countries ?: [],
            'devices' => $devices
        ];
    }

    /**
     * Get Click Counts for all of a user's links
     */
    public function get_user_link_stats( $user_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT target_id,
                   SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks,
                   SUM(CASE WHEN event_type = 'click_variant_b' THEN 1 ELSE 0 END) as clicks_b
            FROM {$this->table_name}
            WHERE user_id = %d
            GROUP BY target_id
        ", $user_id ), OBJECT_K );

        return $results ?: [];
    }

    public function get_user_activity_over_time( $user_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(created_at, '%b %d') as date,
                   SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as views,
                   SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
            FROM {$this->table_name}
            WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY date
            ORDER BY created_at ASC
        ", $user_id ) );
        return $results ?: [];
    }

    public function get_global_activity_over_time() {
        global $wpdb;
        $results = $wpdb->get_results( "
            SELECT DATE_FORMAT(created_at, '%b %d') as date,
                   SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as views,
                   SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
            FROM {$this->table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY date
            ORDER BY created_at ASC
        " );
        return $results ?: [];
    }

    /**
     * Get Recent Social Proof (Leads) for FOMO popups
     */
    public function get_recent_leads( $profile_id, $limit = 5 ) {
        $owner_id = get_post_field('post_author', $profile_id);
        $leads = get_posts([
            'post_type'  => 'saas_lead',
            'author'     => $owner_id,
            'meta_query' => [
                ['key' => '_saas_lead_source_id', 'value' => $profile_id]
            ],
            'numberposts' => $limit,
            'orderby'     => 'date',
            'order'       => 'DESC'
        ]);

        $data = [];
        foreach ($leads as $l) {
            $name = get_post_meta($l->ID, '_saas_lead_name', true);
            $data[] = [
                'name' => $this->mask_name($name),
                'time' => human_time_diff(get_the_time('U', $l->ID), current_time('timestamp')) . ' ago'
            ];
        }
        return $data;
    }

    private function mask_name($name) {
        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            return $parts[0] . ' ' . substr($parts[1], 0, 1) . '.';
        }
        return $name;
    }

    /**
     * Get Global Analytics Summary (for Admin Dashboard)
     */
    public function get_global_summary() {
        global $wpdb;
        $total_views  = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'view'" );
        $total_clicks = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'click'" );
        $total_leads  = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'lead_conversion'" );

        return [
            'views'  => $total_views ?: 0,
            'clicks' => $total_clicks ?: 0,
            'leads'  => $total_leads ?: 0,
        ];
    }

    public function get_growth_data() {
        global $wpdb;
        $results = $wpdb->get_results( "
            SELECT DATE_FORMAT(post_date, '%b') as month, COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'saas_profile' AND post_status = 'publish'
            GROUP BY month
            ORDER BY post_date ASC LIMIT 6
        " );
        return $results ?: [];
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
