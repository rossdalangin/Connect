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

        // Enqueue Scripts
        wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], '1.15.0', true );
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.0.0', true );

        wp_enqueue_script( 'saas-dashboard-js', plugin_dir_url( __FILE__ ) . 'dashboard.js', [ 'sortable-js', 'chart-js' ], '1.0', true );
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
            $profile_obj = get_post($profile_id);
        } else {
            $profile_id = $profile[0]->ID;
            $profile_obj = $profile[0];
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
            <!-- Onboarding Checklist -->
            <div class="saas-onboarding-card">
                <h4>🚀 Get Started Checklist</h4>
                <div style="display:flex; gap:20px; font-size:0.9rem;">
                    <span>[<?php echo $meta['bio'] ? '✓' : ' '; ?>] Bio</span>
                    <span>[<?php echo count($links) > 0 ? '✓' : ' '; ?>] Blocks</span>
                    <span>[ ] Social Links</span>
                </div>
            </div>

            <div class="saas-dashboard-header">
                <h2>Welcome, <?php echo esc_html(wp_get_current_user()->display_name); ?></h2>
                <div class="saas-share-bar">
                    <input type="text" id="saas-my-link" value="<?php echo home_url('/' . $profile_obj->post_name); ?>" readonly>
                    <button id="saas-copy-btn">Copy My Link</button>
                </div>
            </div>
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
                        <option value="social_icons">Social Icons Row</option>
                        <option value="countdown">Countdown Timer</option>
                        <option value="newsletter">Newsletter Form</option>
                    </select>
                    <select name="block_style" id="saas-block-style">
                        <option value="regular">Regular Style</option>
                        <option value="featured">Featured (Pulse)</option>
                        <option value="rainbow">Rainbow Glow</option>
                        <option value="outline">Outline Only</option>
                        <option value="glow">Glow Effect</option>
                    </select>
                    <input type="text" name="title" placeholder="Block Title (e.g. FAQ Question)" required>
                    <input type="url" name="url" placeholder="URL / Embed Link" required>
                    <textarea name="extra" placeholder="Extra content (e.g. FAQ Answer, Price, or Testimonial text)"></textarea>
                    <button type="submit">Add Block</button>
                </form>

                <ul id="saas-links-list" class="sortable">
                    <?php foreach ( $links as $link ) : ?>
                        <li data-id="<?php echo $link->ID; ?>">
                            <span class="handle">:::</span>
                            <strong><?php echo esc_html( $link->post_title ); ?></strong>
                            <span><?php echo esc_url( get_post_meta( $link->ID, '_saas_link_url', true ) ); ?></span>
                            <div class="block-actions">
                                <button class="edit-link button-secondary">Edit</button>
                                <button class="delete-link button-link-delete">Delete</button>
                            </div>
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
                        <label>Theme Primary Color</label>
                        <input type="color" name="theme_color" value="<?php echo esc_attr( $meta['theme_color'] ); ?>">
                    </div>
                    <div class="field">
                        <label>Background Type</label>
                        <select name="bg_type">
                            <option value="flat" <?php selected(get_post_meta($profile_id, '_saas_bg_type', true), 'flat'); ?>>Flat Color</option>
                            <option value="gradient" <?php selected(get_post_meta($profile_id, '_saas_bg_type', true), 'gradient'); ?>>Modern Gradient</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Background Color / Gradient CSS</label>
                        <input type="text" name="bg_value" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_bg_color', true)); ?>" placeholder="#ffffff or linear-gradient(...)">
                    </div>
                    <div class="field">
                        <label>Apply Template</label>
                        <select id="saas-apply-template">
                            <option value="">Select Template...</option>
                            <option value="coach">Coach Funnel</option>
                            <option value="freelancer">Freelancer Portfolio</option>
                            <option value="realtor">Real Estate / Local Biz</option>
                        </select>
                        <button type="button" id="saas-btn-apply-template" class="button button-secondary">Apply & Reset Blocks</button>
                    </div>
                    <button type="submit">Save Changes</button>
                </form>
            </div>

            <!-- Other tabs -->
            <div id="tab-leads" class="saas-tab-content">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3>Your Leads</h3>
                    <a href="<?php echo admin_url('admin-ajax.php?action=saas_export_leads&security='.wp_create_nonce('saas_export_nonce')); ?>" class="button button-secondary">Download CSV</a>
                </div>
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
                                <td data-label="Name"><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_name', true)); ?></td>
                                <td data-label="Email"><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_email', true)); ?></td>
                                <td data-label="Date"><?php echo get_the_date('', $lead->ID); ?></td>
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
                <?php
                $user_analytics = new Saas_Analytics();
                $stats = $user_analytics->get_user_summary($user_id);
                ?>
                <div style="margin-bottom:40px;">
                    <canvas id="saas-analytics-chart" height="150"></canvas>
                </div>
                <div class="saas-stats-grid">
                    <div class="stat-card">
                        <label>Total Views</label>
                        <div class="value"><?php echo number_format($stats['views']); ?></div>
                    </div>
                    <div class="stat-card">
                        <label>Total Clicks</label>
                        <div class="value"><?php echo number_format($stats['clicks']); ?></div>
                    </div>
                    <div class="stat-card">
                        <label>Click-Through Rate</label>
                        <div class="value"><?php echo ($stats['views'] > 0) ? round(($stats['clicks'] / $stats['views']) * 100, 1) : 0; ?>%</div>
                    </div>
                </div>

                <h4>Top Traffic Sources</h4>
                <ul class="saas-analytics-list">
                    <?php if ($stats['referrers']) : foreach ($stats['referrers'] as $ref) : ?>
                        <li><strong><?php echo esc_html($ref->referrer); ?>:</strong> <?php echo $ref->count; ?> visits</li>
                    <?php endforeach; else: ?>
                        <li>No traffic sources recorded yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div id="tab-billing" class="saas-tab-content">
                <h3>Choose Your Plan</h3>
                <div class="saas-plans-grid">
                    <div class="plan-card">
                        <h4>Free</h4>
                        <div class="price">$0/mo</div>
                        <p>Basic Link Hub</p>
                        <button disabled>Current Plan</button>
                    </div>
                    <div class="plan-card featured">
                        <h4>Pro</h4>
                        <div class="price">$19/mo</div>
                        <p>Unlimited Blocks + Lead Gen</p>
                        <form class="checkout-form">
                            <input type="hidden" name="plan_id" value="pro">
                            <select name="gateway">
                                <option value="stripe">Stripe</option>
                                <option value="paypal">PayPal</option>
                            </select>
                            <button type="submit">Upgrade Now</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Live Preview -->
            <div class="saas-preview-pane">
                <h3>Live Preview</h3>
                <div class="preview-frame-container">
                    <iframe id="saas-preview-frame" src="<?php echo home_url('/' . $profile_obj->post_name); ?>"></iframe>
                </div>
            </div>
        </div>

        <!-- Edit Block Modal -->
        <div id="saas-edit-modal" class="saas-modal">
            <div class="saas-modal-content">
                <span class="close-modal">&times;</span>
                <h3>Edit Block</h3>
                <form id="saas-edit-link-form">
                    <input type="hidden" name="link_id" id="edit-link-id">
                    <div class="field">
                        <label>Title</label>
                        <input type="text" name="title" id="edit-link-title" required>
                    </div>
                    <div class="field">
                        <label>URL / Embed</label>
                        <input type="url" name="url" id="edit-link-url" required>
                    </div>
                    <div class="field">
                        <label>Extra Data</label>
                        <textarea name="extra" id="edit-link-extra"></textarea>
                    </div>
                    <button type="submit" class="button button-primary">Save Changes</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
new Saas_Dashboard();
