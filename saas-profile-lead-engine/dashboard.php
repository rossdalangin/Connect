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
        // Enqueue WP Media
        wp_enqueue_media();

        // Only enqueue on pages where the dashboard shortcode is present
        wp_enqueue_style( 'saas-dashboard-css', plugin_dir_url( __FILE__ ) . 'dashboard.css', [], '1.2' );

        // Enqueue Scripts
        wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], '1.15.0', true );
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.0.0', true );

        wp_enqueue_script( 'saas-dashboard-js', plugin_dir_url( __FILE__ ) . 'dashboard.js', [ 'sortable-js', 'chart-js' ], '1.2', true );
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
        $payments = new Saas_Payments();
        $is_pro = $payments->is_pro_user($user_id);

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
                    <span>[<?php echo $is_pro ? '✓' : ' '; ?>] Pro Upgrade</span>
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
                <button data-tab="branding">Branding</button>
                <button data-tab="share">Share & QR</button>
                <button data-tab="automation">Lead Setup</button>
                <button data-tab="leads">Leads</button>
                <button data-tab="analytics">Analytics</button>
                <button data-tab="billing">Billing</button>
            </nav>

            <!-- Links Tab -->
            <div id="tab-links" class="saas-tab-content active">
                <h3>Manage Blocks</h3>

                <!-- Visual Block Picker -->
                <div class="saas-block-picker" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-bottom: 30px;">
                    <div class="picker-item active" data-type="button"><span>🔗</span> Button</div>
                    <div class="picker-item" data-type="video"><span>🎬</span> Video</div>
                    <div class="picker-item" data-type="testimonial"><span>⭐</span> Testim</div>
                    <div class="picker-item" data-type="faq"><span>❓</span> FAQ</div>
                    <div class="picker-item" data-type="pricing"><span>💰</span> Price</div>
                    <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="image_gallery"><span>🖼️</span> Gal <?php if(!$is_pro) echo '🔒'; ?></div>
                    <div class="picker-item" data-type="social_icons"><span>📱</span> Social</div>
                    <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="countdown"><span>⏳</span> Count <?php if(!$is_pro) echo '🔒'; ?></div>
                    <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="newsletter"><span>📧</span> Mail <?php if(!$is_pro) echo '🔒'; ?></div>
                    <div class="picker-item" data-type="milestone"><span>📊</span> Stats</div>
                </div>

                <form id="saas-add-link-form">
                    <input type="hidden" name="block_type" id="saas-block-type-hidden" value="button">
                    <select name="block_style" id="saas-block-style">
                        <option value="regular">Regular Style</option>
                        <option value="featured">Featured (Pulse)</option>
                        <option value="rainbow">Rainbow Glow</option>
                        <option value="outline">Outline Only</option>
                        <option value="glow">Glow Effect</option>
                    </select>
                    <select name="block_animation" id="saas-block-animation">
                        <option value="fadeinup">Fade In Up</option>
                        <option value="bouncein">Bounce In</option>
                        <option value="none">No Animation</option>
                    </select>
                    <input type="text" name="title" placeholder="Block Title (e.g. FAQ Question)" required>
                    <input type="url" name="url" placeholder="URL / Embed Link" required>
                    <textarea name="extra" placeholder="Extra content (e.g. FAQ Answer, Price, or Testimonial text)"></textarea>
                    <button type="submit">Add Block</button>
                </form>

                <ul id="saas-links-list" class="sortable">
                    <?php foreach ( $links as $link ) :
                        $s_date = get_post_meta($link->ID, '_saas_start_date', true);
                        $e_date = get_post_meta($link->ID, '_saas_end_date', true);
                        ?>
                        <li data-id="<?php echo $link->ID; ?>"
                            data-start="<?php echo esc_attr($s_date); ?>"
                            data-end="<?php echo esc_attr($e_date); ?>"
                            data-custom-bg="<?php echo esc_attr(get_post_meta($link->ID, '_saas_custom_bg', true)); ?>"
                            data-custom-text="<?php echo esc_attr(get_post_meta($link->ID, '_saas_custom_text', true)); ?>">
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

                    <div class="field-row" style="display:flex; gap:20px; margin-bottom: 20px;">
                        <div class="field profile-image-field" style="flex:1;">
                            <label>Profile Image</label>
                            <div id="profile-image-preview" class="thumb-preview">
                                <?php if ( has_post_thumbnail($profile_id) ) : ?>
                                    <?php echo get_the_post_thumbnail($profile_id, 'thumbnail'); ?>
                                <?php else : ?>
                                    <div class="image-placeholder">No image</div>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="profile_image_id" id="profile-image-id" value="<?php echo get_post_thumbnail_id($profile_id); ?>">
                            <button type="button" class="upload-btn button" id="profile-image-upload">Upload Photo</button>
                        </div>
                        <div class="field cover-image-field" style="flex:2;">
                            <label>Cover Banner</label>
                            <div id="cover-image-preview" class="cover-preview">
                                <?php
                                $cover_id = get_post_meta($profile_id, '_saas_cover_id', true);
                                if ( $cover_id ) : ?>
                                    <?php echo wp_get_attachment_image($cover_id, 'medium'); ?>
                                <?php else : ?>
                                    <div class="image-placeholder">No banner selected</div>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="cover_image_id" id="cover-image-id" value="<?php echo esc_attr($cover_id); ?>">
                            <button type="button" class="upload-btn button" id="cover-image-upload">Upload Banner</button>
                        </div>
                    </div>

                    <div class="field">
                        <label>Headline</label>
                        <input type="text" name="headline" value="<?php echo esc_attr( $meta['headline'] ); ?>">
                    </div>
                    <div class="field">
                        <label>Bio</label>
                        <textarea name="bio"><?php echo esc_textarea( $meta['bio'] ); ?></textarea>
                    </div>
                    <div class="field">
                        <label>Phone Number (vCard)</label>
                        <input type="text" name="phone" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_phone', true)); ?>">
                    </div>
                    <button type="submit">Save Changes</button>
                </form>
            </div>

            <!-- Automation / Lead Setup Tab -->
            <div id="tab-automation" class="saas-tab-content">
                <h3>Lead Automation Settings</h3>
                <div style="position:relative;">
                <form id="saas-automation-form" class="<?php echo $is_pro ? '' : 'pro-gated'; ?>">
                    <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                    <div class="field">
                        <label>Lead Magnet URL (Automatic Download)</label>
                        <input type="url" name="lead_magnet_url" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_magnet_url', true)); ?>" placeholder="https://yoursite.com/guide.pdf">
                    </div>
                    <div class="field">
                        <label>Redirect URL after Submit</label>
                        <input type="url" name="lead_redirect" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_redirect', true)); ?>" placeholder="https://yoursite.com/thank-you">
                    </div>
                    <div class="field">
                        <label>Webhook URL (Zapier/Make)</label>
                        <input type="url" name="lead_webhook" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_webhook', true)); ?>" placeholder="https://hooks.zapier.com/...">
                    </div>
                    <div class="field">
                        <label>Custom Success Message</label>
                        <input type="text" name="lead_success_msg" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_lead_success_msg', true)); ?>" placeholder="Thank you! We will contact you soon.">
                    </div>
                    <div class="field">
                        <label>Enabled Form Fields</label>
                        <label><input type="checkbox" name="form_field_phone" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_phone', true), 1); ?>> Phone Number</label>
                        <label><input type="checkbox" name="form_field_msg" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_msg', true), 1); ?>> Message/Comments</label>
                    </div>
                    <button type="submit">Save Automation</button>
                </form>
                <?php if(!$is_pro) : ?><div class="pro-overlay"><button type="button" onclick="document.querySelector('[data-tab=billing]').click()">Upgrade to Pro to access Automations</button></div><?php endif; ?>
                </div>
            </div>

            <!-- Share & QR Tab -->
            <div id="tab-share" class="saas-tab-content">
                <h3>Share Your Profile</h3>
                <div class="saas-share-card" style="display:flex; gap:40px; background:#f9f9f9; padding:30px; border-radius:16px;">
                    <div class="qr-section" style="text-align:center;">
                        <h4>Your QR Code</h4>
                        <img src="<?php echo saas_get_profile_qr_url($profile_obj->post_name); ?>" alt="QR Code" style="background:#fff; padding:10px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                        <p><small>Scan to view profile</small></p>
                        <a href="<?php echo saas_get_profile_qr_url($profile_obj->post_name); ?>" download="qr-code.png" class="button">Download PNG</a>
                    </div>
                    <div class="links-section" style="flex:1;">
                        <h4>Direct Link</h4>
                        <div class="saas-share-bar" style="margin-bottom:20px;">
                            <input type="text" value="<?php echo home_url('/' . $profile_obj->post_name); ?>" readonly style="width:100%; margin-bottom:10px;">
                            <button class="button saas-copy-btn">Copy Link</button>
                        </div>
                        <h4>Social Share</h4>
                        <div style="display:flex; gap:10px;">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(home_url($profile_obj->post_name)); ?>" target="_blank" class="button">𝕏 Share</a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(home_url($profile_obj->post_name)); ?>" target="_blank" class="button">LinkedIn</a>
                            <a href="https://wa.me/?text=<?php echo urlencode(home_url($profile_obj->post_name)); ?>" target="_blank" class="button">WhatsApp</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Branding Tab -->
            <div id="tab-branding" class="saas-tab-content">
                <h3>Branding & Styling</h3>
                <form id="saas-branding-form">
                    <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
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
                        <label>Button Shape</label>
                        <select name="btn_shape">
                            <option value="pill" <?php selected(get_post_meta($profile_id, '_saas_btn_shape', true), 'pill'); ?>>Pill (Modern)</option>
                            <option value="rounded" <?php selected(get_post_meta($profile_id, '_saas_btn_shape', true), 'rounded'); ?>>Rounded (Soft)</option>
                            <option value="square" <?php selected(get_post_meta($profile_id, '_saas_btn_shape', true), 'square'); ?>>Square (Sharp)</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Font Family</label>
                        <select name="font_family">
                            <option value="'Inter', sans-serif" <?php selected(get_post_meta($profile_id, '_saas_font_family', true), "'Inter', sans-serif"); ?>>Inter (Modern)</option>
                            <option value="'Roboto', sans-serif" <?php selected(get_post_meta($profile_id, '_saas_font_family', true), "'Roboto', sans-serif"); ?>>Roboto (Classic)</option>
                            <option value="'Georgia', serif" <?php selected(get_post_meta($profile_id, '_saas_font_family', true), "'Georgia', serif"); ?>>Georgia (Elegant)</option>
                            <option value="'Montserrat', sans-serif" <?php selected(get_post_meta($profile_id, '_saas_font_family', true), "'Montserrat', sans-serif"); ?>>Montserrat (Geometric)</option>
                            <option value="'Playfair Display', serif" <?php selected(get_post_meta($profile_id, '_saas_font_family', true), "'Playfair Display', serif"); ?>>Playfair (Luxury)</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Apply Template</label>
                        <select id="saas-apply-template">
                            <option value="">Select Template...</option>
                            <option value="coach">Coach Funnel</option>
                            <option value="freelancer">Freelancer Portfolio</option>
                            <option value="realtor">Real Estate / Local Biz</option>
                            <option value="elite_card">Elite Digital Card</option>
                        </select>
                        <button type="button" id="saas-btn-apply-template" class="button button-secondary">Apply & Reset Blocks</button>
                    </div>
                    <button type="submit">Save Branding</button>
                </form>
            </div>

            <!-- Other tabs -->
            <div id="tab-leads" class="saas-tab-content">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3>Your Leads</h3>
                    <div style="display:flex; gap:10px;">
                        <a href="<?php echo admin_url('admin-ajax.php?action=saas_export_leads&security='.wp_create_nonce('saas_export_nonce')); ?>" class="button button-secondary">Download CSV</a>
                    </div>
                </div>
                <?php
                $leads = get_posts([
                    'post_type' => 'saas_lead',
                    'post_author' => $user_id,
                    'numberposts' => 50
                ]);
                if ($leads) : ?>
                    <table class="saas-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($leads as $lead) :
                            $status = get_post_meta($lead->ID, '_saas_lead_status', true) ?: 'New';
                            ?>
                            <tr class="lead-row-<?php echo esc_attr(strtolower($status)); ?>">
                                <td data-label="Name"><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_name', true)); ?></td>
                                <td data-label="Email"><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_email', true)); ?></td>
                                <td data-label="Status">
                                    <span class="status-badge <?php echo esc_attr(strtolower($status)); ?>"><?php echo esc_html($status); ?></span>
                                </td>
                                <td data-label="Date"><?php echo get_the_date('', $lead->ID); ?></td>
                                <td data-label="Actions">
                                    <button class="view-lead-btn" data-id="<?php echo $lead->ID; ?>">View</button>
                                    <button class="delete-lead-btn" data-id="<?php echo $lead->ID; ?>" style="color:#ff7675; border:none; background:none; cursor:pointer;">Delete</button>
                                </td>
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

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; margin-top:30px;">
                    <div>
                        <h4>Top Traffic Sources</h4>
                        <ul class="saas-analytics-list">
                            <?php if ($stats['referrers']) : foreach ($stats['referrers'] as $ref) : ?>
                                <li><strong><?php echo esc_html($ref->referrer); ?>:</strong> <?php echo $ref->count; ?> visits</li>
                            <?php endforeach; else: ?>
                                <li>No traffic sources recorded yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div>
                        <h4>Device Breakdown</h4>
                        <ul class="saas-analytics-list">
                            <?php foreach ($stats['devices'] as $dev) : ?>
                                <li><strong><?php echo esc_html($dev->label); ?>:</strong> <?php echo $dev->count; ?> views</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
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

        <!-- Lead View Modal -->
        <div id="saas-lead-modal" class="saas-modal">
            <div class="saas-modal-content">
                <span class="close-modal">&times;</span>
                <h3>Lead Details</h3>
                <div id="lead-details-content">
                    <!-- Loaded via AJAX -->
                    <p>Loading...</p>
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
                    <div class="field-row <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>" style="display:flex; gap:10px;">
                        <div class="field">
                            <label>Custom BG Color <?php if(!$is_pro) echo '🔒'; ?></label>
                            <input type="color" name="custom_bg" id="edit-link-bg" <?php if(!$is_pro) echo 'disabled'; ?>>
                        </div>
                        <div class="field">
                            <label>Custom Text Color <?php if(!$is_pro) echo '🔒'; ?></label>
                            <input type="color" name="custom_text" id="edit-link-text" <?php if(!$is_pro) echo 'disabled'; ?>>
                        </div>
                    </div>
                    <div class="field">
                        <label>Extra Data</label>
                        <textarea name="extra" id="edit-link-extra"></textarea>
                    </div>
                    <div class="field-row" style="display:flex; gap:10px;">
                        <div class="field">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="edit-link-start">
                        </div>
                        <div class="field">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="edit-link-end">
                        </div>
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
