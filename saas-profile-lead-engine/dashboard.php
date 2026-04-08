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
        wp_enqueue_media();
        wp_enqueue_style( 'saas-dashboard-css', plugin_dir_url( __FILE__ ) . 'dashboard.css', [], '2.1' );
        wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], '1.15.0', true );
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.0.0', true );
        wp_enqueue_script( 'saas-dashboard-js', plugin_dir_url( __FILE__ ) . 'dashboard.js', [ 'sortable-js', 'chart-js' ], '2.1', true );
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
        $all_user_profiles = get_posts([
            'post_type'   => 'saas_profile',
            'post_author' => $user_id,
            'numberposts' => -1,
        ]);

        $active_profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;
        $payments = new Saas_Payments();

        if ( empty( $all_user_profiles ) ) {
            $profile_id = wp_insert_post([
                'post_type'   => 'saas_profile',
                'post_title'  => wp_get_current_user()->display_name,
                'post_status' => 'publish',
                'post_author' => $user_id,
            ]);
            $profile_obj = get_post($profile_id);
            $active_profile_id = $profile_id;
        } else {
            $profile_obj = null;
            if ($active_profile_id) {
                foreach($all_user_profiles as $p) {
                    if ($p->ID === $active_profile_id) { $profile_obj = $p; break; }
                }
            }
            if (!$profile_obj) {
                $profile_obj = $all_user_profiles[0];
                $active_profile_id = $profile_obj->ID;
            }
            $profile_id = $active_profile_id;
        }

        $meta = saas_get_profile_meta( $profile_id );
        $is_pro = saas_is_profile_licensed($profile_id);
        $analytics = new Saas_Analytics();
        $link_stats = $analytics->get_user_link_stats($user_id);

        $links = get_posts([
            'post_type'   => 'saas_link',
            'meta_query' => [['key' => '_saas_profile_id', 'value' => $profile_id]],
            'orderby'     => 'menu_order',
            'order'       => 'ASC',
            'numberposts' => -1,
        ]);

        ob_start();
        ?>
        <div id="saas-dashboard">
            <div class="dashboard-main-area">

                <!-- Header -->
                <div class="saas-dashboard-header">
                    <div class="profile-switcher-wrapper">
                        <h2 class="profile-title"><?php echo esc_html($profile_obj->post_title); ?> ▾</h2>
                        <div class="profile-dropdown">
                            <?php foreach($all_user_profiles as $up) : ?>
                                <div class="dropdown-item-wrapper <?php echo ($up->ID == $active_profile_id) ? 'active' : ''; ?>">
                                    <a href="?profile_id=<?php echo $up->ID; ?>" class="dropdown-item"><?php echo esc_html($up->post_title); ?></a>
                                    <button class="clone-profile-btn" data-id="<?php echo $up->ID; ?>" title="Clone Profile">📋</button>
                                </div>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <button id="saas-add-profile-trigger" class="add-profile-btn">+ New Profile</button>
                        </div>
                    </div>
                    <div class="saas-share-bar">
                        <input type="text" id="saas-my-link" value="<?php echo home_url('/' . $profile_obj->post_name); ?>" readonly>
                        <button id="saas-copy-btn" class="btn-primary">Copy Link</button>
                    </div>
                </div>

                <!-- Tabs -->
                <nav class="saas-tabs">
                    <button class="active" data-tab="links">🔗 Links</button>
                    <button data-tab="profile">👤 Profile</button>
                    <button data-tab="branding">🎨 Style</button>
                    <button data-tab="leads">👥 Leads</button>
                    <button data-tab="analytics">📈 Stats</button>
                    <button data-tab="automation">🎯 Rules</button>
                    <button data-tab="billing">💳 Pro</button>
                </nav>

                <!-- Tab Contents -->

                <div id="tab-links" class="saas-tab-content active">
                    <div class="dashboard-card">
                        <h3>Build Your Page</h3>
                        <div class="saas-block-picker">
                            <div class="picker-item active" data-type="button"><span>🔗</span> Button</div>
                            <div class="picker-item" data-type="video"><span>🎬</span> Video</div>
                            <div class="picker-item" data-type="testimonial"><span>⭐</span> Quote</div>
                            <div class="picker-item" data-type="faq"><span>❓</span> FAQ</div>
                            <div class="picker-item" data-type="pricing"><span>💰</span> Price</div>
                            <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="image_gallery"><span>🖼️</span> Gallery</div>
                            <div class="picker-item" data-type="social_icons"><span>📱</span> Social</div>
                            <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="newsletter"><span>📧</span> Mail</div>
                        </div>

                        <form id="saas-add-link-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <input type="hidden" name="block_type" id="saas-block-type-hidden" value="button">
                            <div class="field"><input type="text" name="title" placeholder="Block Title" required></div>
                            <div class="field"><input type="url" name="url" placeholder="Destination URL" required></div>
                            <div class="field"><textarea name="extra" placeholder="Extra content (FAQ answer, quote, or price)"></textarea></div>
                            <button type="submit" class="btn-primary">Add Block</button>
                        </form>

                        <ul id="saas-links-list" class="sortable" style="margin-top:40px;">
                            <?php foreach ( $links as $link ) :
                                $type = get_post_meta($link->ID, '_saas_block_type', true);
                                $extra = get_post_meta($link->ID, '_saas_testimonial_text', true) ?: get_post_meta($link->ID, '_saas_faq_answer', true);
                                if (!$extra) {
                                    $price = get_post_meta($link->ID, '_saas_price', true);
                                    $feats = get_post_meta($link->ID, '_saas_features', true);
                                    if ($price) $extra = $price . ($feats ? "\n" . implode("\n", $feats) : "");
                                }
                                ?>
                                <li data-id="<?php echo $link->ID; ?>"
                                    data-type="<?php echo esc_attr($type); ?>"
                                    data-style="<?php echo esc_attr(get_post_meta($link->ID, '_saas_block_style', true)); ?>"
                                    data-animation="<?php echo esc_attr(get_post_meta($link->ID, '_saas_block_animation', true)); ?>"
                                    data-extra="<?php echo esc_attr($extra); ?>"
                                    data-start="<?php echo esc_attr(get_post_meta($link->ID, '_saas_start_date', true)); ?>"
                                    data-end="<?php echo esc_attr(get_post_meta($link->ID, '_saas_end_date', true)); ?>"
                                    data-url-mobile="<?php echo esc_attr(get_post_meta($link->ID, '_saas_url_mobile', true)); ?>"
                                    data-url-geo="<?php echo esc_attr(get_post_meta($link->ID, '_saas_url_geo', true)); ?>"
                                    data-geo-country="<?php echo esc_attr(get_post_meta($link->ID, '_saas_url_geo_country', true)); ?>"
                                    data-ab-title="<?php echo esc_attr(get_post_meta($link->ID, '_saas_ab_title_b', true)); ?>"
                                    data-ab-url="<?php echo esc_attr(get_post_meta($link->ID, '_saas_ab_url_b', true)); ?>"
                                    data-hour-from="<?php echo esc_attr(get_post_meta($link->ID, '_saas_hour_from', true)); ?>"
                                    data-hour-to="<?php echo esc_attr(get_post_meta($link->ID, '_saas_hour_to', true)); ?>"
                                    data-custom-bg="<?php echo esc_attr(get_post_meta($link->ID, '_saas_custom_bg', true)); ?>"
                                    data-custom-text="<?php echo esc_attr(get_post_meta($link->ID, '_saas_custom_text', true)); ?>"
                                    data-password="<?php echo esc_attr(get_post_meta($link->ID, '_saas_link_password', true)); ?>">
                                    <span class="handle">⠿</span>
                                    <div class="link-info">
                                        <strong class="link-title"><?php echo esc_html( $link->post_title ); ?></strong>
                                        <span class="link-url"><?php echo esc_url( get_post_meta( $link->ID, '_saas_link_url', true ) ?: '#' ); ?></span>
                                    </div>
                                    <div class="block-actions">
                                        <button class="edit-link btn-secondary">Edit</button>
                                        <button class="delete-link btn-secondary" style="color:var(--danger);">Delete</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="tab-profile" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Identity Settings</h3>
                        <form id="saas-profile-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <div class="field">
                                <label>Headline</label>
                                <div style="display:flex; gap:10px;">
                                    <input type="text" name="headline" value="<?php echo esc_attr( $meta['headline'] ); ?>" style="flex:1;">
                                    <button type="button" class="ai-assist-btn button" data-target="headline">✨</button>
                                </div>
                            </div>
                            <div class="field">
                                <label>Bio</label>
                                <textarea name="bio" rows="4"><?php echo esc_textarea( $meta['bio'] ); ?></textarea>
                            </div>
                            <div class="field">
                                <label>Company / Org</label>
                                <input type="text" name="company" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_company', true)); ?>">
                            </div>
                            <button type="submit" class="btn-primary">Save Profile</button>
                        </form>
                    </div>
                </div>

                <div id="tab-branding" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Vibe & Style</h3>
                        <form id="saas-branding-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <div class="field">
                                <label>Brand Color</label>
                                <input type="color" name="theme_color" value="<?php echo esc_attr( $meta['theme_color'] ); ?>">
                            </div>
                            <div class="field">
                                <label>Background</label>
                                <select name="bg_type">
                                    <option value="flat" <?php selected(get_post_meta($profile_id, '_saas_bg_type', true), 'flat'); ?>>Flat</option>
                                    <option value="gradient" <?php selected(get_post_meta($profile_id, '_saas_bg_type', true), 'gradient'); ?>>Gradient</option>
                                    <option value="mesh" <?php selected(get_post_meta($profile_id, '_saas_bg_type', true), 'mesh'); ?>>Elite Mesh</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary">Update Style</button>
                        </form>
                    </div>
                </div>

                <div id="tab-leads" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Captured Leads</h3>
                        <?php
                        $leads = get_posts(['post_type' => 'saas_lead', 'post_author' => $user_id, 'numberposts' => 20]);
                        if ($leads) : ?>
                            <table class="saas-table">
                                <thead><tr><th>Name</th><th>Email</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($leads as $lead) : ?>
                                        <tr>
                                            <td><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_name', true)); ?></td>
                                            <td><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_email', true)); ?></td>
                                            <td><?php echo get_the_date('M j', $lead->ID); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p style="color:var(--text-muted);">No leads captured yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="tab-analytics" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Profile Stats</h3>
                        <div style="height: 250px; margin-bottom: 30px;"><canvas id="saas-analytics-chart"></canvas></div>
                        <?php $stats = $analytics->get_user_summary($user_id); ?>
                        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
                            <div class="dashboard-card" style="text-align:center; padding:15px;">
                                <small>VIEWS</small><div style="font-size:1.5rem; font-weight:900; color:var(--primary);"><?php echo $stats['views']; ?></div>
                            </div>
                            <div class="dashboard-card" style="text-align:center; padding:15px;">
                                <small>CLICKS</small><div style="font-size:1.5rem; font-weight:900; color:var(--secondary);"><?php echo $stats['clicks']; ?></div>
                            </div>
                            <div class="dashboard-card" style="text-align:center; padding:15px;">
                                <small>LEADS</small><div style="font-size:1.5rem; font-weight:900; color:var(--accent);"><?php echo $stats['leads']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-automation" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Automation Rules</h3>
                        <form id="saas-automation-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <div class="field">
                                <label>Webhook URL</label>
                                <input type="url" name="lead_webhook" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_webhook', true)); ?>" placeholder="Zapier or Make URL">
                            </div>
                            <div class="field">
                                <label>Redirect after Signup</label>
                                <input type="url" name="lead_redirect" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_redirect', true)); ?>" placeholder="Thank you page URL">
                            </div>
                            <button type="submit" class="btn-primary">Save Rules</button>
                        </form>
                    </div>
                </div>

                <div id="tab-billing" class="saas-tab-content">
                    <div class="dashboard-card" style="text-align:center;">
                        <h3>Go Pro 🚀</h3>
                        <p>Unlock galleries, mesh backgrounds, and unlimited profiles.</p>
                        <div class="plan-card" style="background:var(--primary-soft); padding:30px; border-radius:20px; border:2px solid var(--primary); max-width:300px; margin:20px auto;">
                            <h4 style="font-size:1.5rem;">Elite Pro</h4>
                            <div style="font-size:2rem; font-weight:900; margin:10px 0;">$19/mo</div>
                            <?php if ($is_pro) : ?>
                                <button disabled class="btn-primary" style="width:100%;">Active</button>
                            <?php else : ?>
                                <button id="saas-simulate-pro" class="btn-primary" style="width:100%;">Upgrade</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div> <!-- End Main Area -->

            <div class="saas-preview-pane">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <strong>Live Preview</strong>
                    <button onclick="document.getElementById('saas-preview-frame').contentWindow.location.reload();" class="button">🔄</button>
                </div>
                <div class="preview-frame-container">
                    <iframe id="saas-preview-frame" src="<?php echo home_url('/' . $profile_obj->post_name); ?>"></iframe>
                </div>
            </div>
        </div>

        <div id="saas-edit-modal" class="saas-modal">
            <div class="saas-modal-content">
                <span class="close-modal">&times;</span>
                <h3>Edit Block</h3>
                <form id="saas-edit-link-form">
                    <input type="hidden" name="link_id" id="edit-link-id">

                    <div class="field"><label>Block Title</label><input type="text" name="title" id="edit-link-title" required></div>
                    <div class="field"><label>URL / Destination</label><input type="url" name="url" id="edit-link-url" required></div>
                    <div class="field"><label>Extra Content (FAQ, Quote, Price)</label><textarea name="extra" id="edit-link-extra"></textarea></div>

                    <button type="button" class="button toggle-advanced" style="width:100%; margin-bottom:20px; background:#f1f5f9; color:#475569;">⚙️ Advanced Settings</button>

                    <div id="edit-advanced-fields" style="display:none; padding:20px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px;">
                        <div class="field">
                            <label>Style & Animation</label>
                            <div style="display:flex; gap:10px;">
                                <select name="block_style" id="edit-link-style" style="flex:1;">
                                    <option value="regular">Regular</option>
                                    <option value="featured">Featured (Pulse)</option>
                                    <option value="outline">Outline</option>
                                    <option value="glow">Glow</option>
                                </select>
                                <select name="block_animation" id="edit-link-animation" style="flex:1;">
                                    <option value="none">No Animation</option>
                                    <option value="fadeinup">Fade In Up</option>
                                    <option value="bouncein">Bounce In</option>
                                </select>
                            </div>
                        </div>

                        <div class="field">
                            <label>Custom Colors (Pro Only)</label>
                            <div style="display:flex; gap:10px;">
                                <input type="color" name="custom_bg" id="edit-link-bg" style="width:50px;">
                                <input type="color" name="custom_text" id="edit-link-text" style="width:50px;">
                            </div>
                        </div>

                        <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                            <label>A/B Testing Title B (Pro)</label>
                            <input type="text" name="ab_title_b" id="edit-link-ab-title" placeholder="Variant B Title">
                        </div>

                        <div class="field">
                            <label>Scheduling (Start / End Date)</label>
                            <div style="display:flex; gap:10px;">
                                <input type="date" name="start_date" id="edit-link-start">
                                <input type="date" name="end_date" id="edit-link-end">
                            </div>
                        </div>

                        <div class="field">
                            <label>Hour Range (0-23)</label>
                            <div style="display:flex; gap:10px;">
                                <input type="number" name="hour_from" id="edit-link-hour-from" placeholder="From" min="0" max="23">
                                <input type="number" name="hour_to" id="edit-link-hour-to" placeholder="To" min="0" max="23">
                            </div>
                        </div>

                        <div class="field">
                            <label>Password Protection</label>
                            <input type="text" name="link_password" id="edit-link-pass" placeholder="Enter password to lock block">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;">Save Changes</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
new Saas_Dashboard();
