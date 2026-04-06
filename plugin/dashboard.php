<?php
/**
 * User Dashboard UI and Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Dashboard {
    public function __construct() {
        add_shortcode( 'saas_dashboard', [ $this, 'render_dashboard' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_dashboard_scripts' ] );
    }

    public function enqueue_dashboard_scripts() {
        // Only enqueue on pages where the dashboard shortcode is present
        wp_enqueue_style( 'saas-dashboard-css', plugin_dir_url( __FILE__ ) . 'dashboard.css', [], '1.0' );
        wp_enqueue_script( 'saas-dashboard-js', plugin_dir_url( __FILE__ ) . 'dashboard.js', [], '1.0', true );
        wp_localize_script( 'saas-dashboard-js', 'saas_dashboard_data', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'saas_dashboard_nonce' )
        ]);
    }

    public function render_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<p>Please log in to manage your profile.</p>';
        }

        $user_id = get_current_user_id();
        $profile = get_posts([
            'post_type'   => 'saas_profile',
            'post_author' => $user_id,
            'numberposts' => 1,
        ]);

        if ( empty( $profile ) ) {
            // Auto-create profile if missing
            $profile_id = wp_insert_post([
                'post_type'   => 'saas_profile',
                'post_title'  => wp_get_current_user()->display_name,
                'post_status' => 'publish',
                'post_author' => $user_id,
            ]);
        } else {
            $profile_id = $profile[0]->ID;
        }

        $meta = saas_get_profile_meta( $profile_id );
        $links = get_posts([
            'post_type'   => 'saas_link',
            'post_author' => $user_id,
            'orderby'     => 'meta_value_num',
            'meta_key'    => '_saas_priority',
            'order'       => 'ASC',
            'numberposts' => -1,
        ]);

        ob_start();
        ?>
        <div id="saas-dashboard">
            <nav class="saas-tabs">
                <button class="active" data-tab="links">Links</button>
                <button data-tab="profile">Profile</button>
                <button data-tab="leads">Leads</button>
                <button data-tab="analytics">Analytics</button>
                <button data-tab="billing">Billing</button>
            </nav>

            <!-- Links Tab -->
            <div id="tab-links" class="saas-tab-content active">
                <h3>Manage Blocks</h3>
                <form id="saas-add-link-form">
                    <select name="block_type" id="saas-block-type">
                        <option value="button">Button Link</option>
                        <option value="video">Video Embed</option>
                        <option value="testimonial">Testimonial</option>
                        <option value="faq">FAQ Item</option>
                        <option value="pricing">Pricing Table</option>
                        <option value="image_gallery">Image Gallery</option>
                        <option value="calendar">Calendar Embed</option>
                    </select>
                    <input type="text" name="title" placeholder="Block Title" required>
                    <input type="url" name="url" placeholder="URL / Embed Link" required>
                    <button type="submit">Add Block</button>
                </form>

                <ul id="saas-links-list" class="sortable">
                    <?php foreach ( $links as $link ) : ?>
                        <li data-id="<?php echo $link->ID; ?>">
                            <span class="handle">:::</span>
                            <strong><?php echo esc_html( $link->post_title ); ?></strong>
                            <span><?php echo esc_url( get_post_meta( $link->ID, '_saas_link_url', true ) ); ?></span>
                            <button class="delete-link">Delete</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Profile Tab -->
            <div id="tab-profile" class="saas-tab-content">
                <h3>Profile Settings</h3>
                <form id="saas-profile-form">
                    <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                    <div class="field">
                        <label>Headline</label>
                        <input type="text" name="headline" value="<?php echo esc_attr( $meta['headline'] ); ?>">
                    </div>
                    <div class="field">
                        <label>Bio</label>
                        <textarea name="bio"><?php echo esc_textarea( $meta['bio'] ); ?></textarea>
                    </div>
                    <div class="field">
                        <label>Theme Color</label>
                        <input type="color" name="theme_color" value="<?php echo esc_attr( $meta['theme_color'] ); ?>">
                    </div>
                    <button type="submit">Save Changes</button>
                </form>
            </div>

            <!-- Other tabs -->
            <div id="tab-leads" class="saas-tab-content">
                <h3>Your Leads</h3>
                <?php
                $leads = get_posts([
                    'post_type' => 'saas_lead',
                    'post_author' => $user_id,
                    'numberposts' => 20
                ]);
                if ($leads) : ?>
                    <table class="saas-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($leads as $lead) : ?>
                            <tr>
                                <td><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_name', true)); ?></td>
                                <td><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_email', true)); ?></td>
                                <td><?php echo get_the_date('', $lead->ID); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>No leads captured yet.</p>
                <?php endif; ?>
            </div>
            <div id="tab-analytics" class="saas-tab-content">
                <h3>Profile Insights</h3>
                <p>Detailed breakdown of views and link clicks.</p>
            </div>
            <div id="tab-billing" class="saas-tab-content">
                <h3>Plan & Billing</h3>
                <p>Manage your subscription and payment methods.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
new Saas_Dashboard();
