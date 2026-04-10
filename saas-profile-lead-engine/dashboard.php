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
        wp_enqueue_style( 'saas-dashboard-css', plugin_dir_url( __FILE__ ) . 'dashboard.css', [], '2.3' );
        wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], '1.15.0', true );
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.0.0', true );
        wp_enqueue_script( 'saas-dashboard-js', plugin_dir_url( __FILE__ ) . 'dashboard.js', [ 'sortable-js', 'chart-js' ], '2.3', true );
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

        $profile_bg_type = get_post_meta($profile_id, '_saas_bg_type', true) ?: 'flat';
        $profile_bg_val  = ($profile_bg_type === 'gradient') ? get_post_meta($profile_id, '_saas_bg_gradient', true) : get_post_meta($profile_id, '_saas_bg_color', true);

        ob_start();
        ?>
        <div id="saas-dashboard">
            <div class="dashboard-main-area">
                <!-- Onboarding Checklist -->
                <div class="saas-onboarding-card dashboard-card">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h4 style="margin:0;">🚀 Get Started Checklist</h4>
                            <div style="display:flex; gap:15px; margin-top:8px; font-size:0.8rem;">
                                <span><?php echo $meta['headline'] ? '[✓]' : '[ ]'; ?> Bio</span>
                                <span><?php echo count($links) > 0 ? '[✓]' : '[ ]'; ?> Blocks</span>
                                <span><?php echo $is_pro ? '[✓]' : '[ ]'; ?> Pro Upgrade</span>
                            </div>
                        </div>
                        <button class="button" onclick="document.getElementById('saas-wizard-modal').style.display='block'">Launch Setup Wizard</button>
                    </div>
                </div>

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
                        <?php
                        $new_leads_count = get_posts(['post_type' => 'saas_lead', 'post_author' => $user_id, 'meta_key' => '_saas_lead_status', 'meta_value' => 'New', 'fields' => 'ids', 'numberposts' => -1]);
                        $count = count($new_leads_count);
                        ?>
                        <div class="saas-notif-bell" onclick="document.getElementById('saas-notif-modal').style.display='block'">🔔<?php if($count > 0) echo '<span class="notif-count">'.$count.'</span>'; ?></div>
                        <input type="text" id="saas-my-link" value="<?php echo home_url('/' . $profile_obj->post_name); ?>" readonly>
                        <button id="saas-copy-btn" class="btn-primary">Copy Link</button>
                    </div>
                </div>

                <nav class="saas-tabs">
                    <button class="active" data-tab="links">🔗 Blocks</button>
                    <button data-tab="profile">👤 Profile</button>
                    <button data-tab="branding">🎨 Vibe</button>
                    <button data-tab="leads">👥 Leads</button>
                    <button data-tab="analytics">📈 Stats</button>
                    <button data-tab="integrations">🔌 Sync</button>
                    <button data-tab="referrals">💸 Earn</button>
                    <button data-tab="automation">⚙️ Settings</button>
                    <button data-tab="billing">💳 Pro</button>
                </nav>

                <div id="tab-links" class="saas-tab-content active">
                    <div class="link-tab-grid">
                        <div class="block-picker-sidebar">
                            <div class="dashboard-card">
                                <h3>Manage Blocks</h3>
                                <div class="saas-block-picker">
                                    <div class="picker-item active" data-type="button"><span>🔗</span> Button</div>
                                    <div class="picker-item" data-type="video"><span>🎬</span> Video</div>
                                    <div class="picker-item" data-type="testimonial"><span>⭐</span> Testim</div>
                                    <div class="picker-item" data-type="faq"><span>❓</span> FAQ</div>
                                    <div class="picker-item" data-type="pricing"><span>💰</span> Price</div>
                                    <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="image_gallery"><span>🖼️</span> Gal <span class="pro-badge">Pro</span></div>
                                    <div class="picker-item" data-type="social_icons"><span>📱</span> Social</div>
                                    <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="newsletter"><span>📧</span> Mail <span class="pro-badge">Pro</span></div>
                                </div>

                                <form id="saas-add-link-form">
                                    <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                                    <input type="hidden" name="block_type" id="saas-block-type-hidden" value="button">
                                    <div class="field"><input type="text" name="title" placeholder="Block Title (e.g. FAQ Question)" required></div>
                                    <div class="field"><input type="url" name="url" placeholder="URL / Embed Link" required></div>
                                    <div class="field"><textarea name="extra" placeholder="Extra content (e.g. FAQ Answer, Price, Quote)" rows="2"></textarea></div>
                                    <button type="submit" class="btn-primary" style="width:100%;">Add Block</button>
                                </form>
                            </div>
                        </div>

                        <div class="links-display-area">
                            <ul id="saas-links-list" class="sortable">
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
                                        <button class="edit-link button" title="Edit">✏️</button>
                                        <button class="clone-link button" title="Duplicate">📋</button>
                                        <button class="delete-link button" title="Delete" style="color:var(--danger);">🗑️</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div id="tab-profile" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Identity Settings</h3>
                        <form id="saas-profile-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <div class="field">
                                <label>Profile Headline</label>
                                <div style="display:flex; gap:10px;">
                                    <input type="text" name="headline" value="<?php echo esc_attr( $meta['headline'] ); ?>" style="flex:1;">
                                    <button type="button" class="ai-assist-btn button" data-target="headline">✨</button>
                                </div>
                            </div>
                            <div class="field">
                                <label>Short Biography</label>
                                <textarea name="bio" rows="4"><?php echo esc_textarea( $meta['bio'] ); ?></textarea>
                            </div>
                            <div class="field">
                                <label>Your Niche / Category</label>
                                <select name="niche" id="profile-niche">
                                    <?php
                                    $niche = get_post_meta($profile_id, '_saas_niche', true);
                                    $niches = ['coach' => 'Coach', 'creator' => 'Creator', 'realtor' => 'Real Estate', 'business' => 'Business'];
                                    foreach($niches as $k => $v) : ?>
                                        <option value="<?php echo $k; ?>" <?php selected($niche, $k); ?>><?php echo $v; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label>Company / Organization</label>
                                <input type="text" name="company" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_company', true)); ?>">
                            </div>
                            <button type="submit" class="btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>

                <div id="tab-branding" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Style & Identity</h3>
                        <form id="saas-branding-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">

                            <div class="field">
                                <label>Base Theme</label>
                                <select name="profile_theme" id="profile-theme-select">
                                    <option value="light" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'light'); ?>>Light Mode</option>
                                    <option value="dark" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'dark'); ?>>Dark Mode</option>
                                    <option value="vibrant" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'vibrant'); ?>>Vibrant (Gradient)</option>
                                    <option value="luxury" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'luxury'); ?>>Luxury (Gold/Black)</option>
                                </select>
                            </div>

                            <div class="field" id="saas-bg-value-wrapper">
                                <label id="saas-bg-value-label">Background Value</label>
                                <input type="text" name="bg_value" id="saas-bg-value-input" value="<?php echo esc_attr($profile_bg_val ?: '#f3f3f1'); ?>">
                                <p style="font-size:0.7rem; color:#888; margin-top:5px;">Hex color (e.g. #ffffff) or CSS gradient.</p>
                            </div>

                            <div class="field">
                                <label>Theme Accent Color</label>
                                <input type="color" name="theme_color" value="<?php echo esc_attr( $meta['theme_color'] ); ?>">
                            </div>

                            <div class="field">
                                <label>Background Engine</label>
                                <select name="bg_type" id="profile-bg-type">
                                    <option value="flat" <?php selected(get_post_meta($profile_id, '_saas_bg_type', true), 'flat'); ?>>Clean Flat</option>
                                    <option value="gradient" <?php selected(get_post_meta($profile_id, '_saas_bg_type', true), 'gradient'); ?>>Modern Gradient</option>
                                    <option value="mesh" <?php selected(get_post_meta($profile_id, '_saas_bg_type', true), 'mesh'); ?>>Elite Mesh (Pro)</option>
                                    <option value="particles" <?php selected(get_post_meta($profile_id, '_saas_bg_type', true), 'particles'); ?>>Interactive Particles (Pro)</option>
                                </select>
                            </div>

                            <div class="field">
                                <label>Button Aesthetics</label>
                                <select name="btn_shape">
                                    <option value="pill" <?php selected(get_post_meta($profile_id, '_saas_btn_shape', true), 'pill'); ?>>Pill (Max Rounded)</option>
                                    <option value="rounded" <?php selected(get_post_meta($profile_id, '_saas_btn_shape', true), 'rounded'); ?>>Rounded Corners</option>
                                    <option value="square" <?php selected(get_post_meta($profile_id, '_saas_btn_shape', true), 'square'); ?>>Sharp Square</option>
                                </select>
                            </div>

                            <div class="field">
                                <label>Quick Style Presets</label>
                                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:10px;">
                                    <button type="button" class="preset-btn button" data-preset="midnight">🌑 Midnight</button>
                                    <button type="button" class="preset-btn button" data-preset="glassy">💎 Glassy</button>
                                    <button type="button" class="preset-btn button" data-preset="vibrant">🌈 Vibrant</button>
                                    <button type="button" class="preset-btn button" data-preset="minimal">⚪ Minimal</button>
                                    <button type="button" class="preset-btn button" data-preset="luxury">⚜️ Luxury</button>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary">Apply Styles</button>
                        </form>
                    </div>
                </div>

                <div id="tab-leads" class="saas-tab-content">
                    <div class="dashboard-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                            <h3>Captured Leads</h3>
                            <a href="<?php echo admin_url('admin-ajax.php?action=saas_export_leads&security='.wp_create_nonce('saas_export_nonce')); ?>" class="button">📥 Export CSV</a>
                        </div>
                        <div class="saas-table-wrapper">
                            <?php
                            $leads = get_posts(['post_type' => 'saas_lead', 'post_author' => $user_id, 'numberposts' => 50]);
                            if ($leads) : ?>
                                <button id="saas-bulk-delete-leads" class="button" style="margin-bottom:10px; color:var(--danger); display:none;">🗑️ Delete Selected</button>
                                <table class="saas-table">
                                    <thead><tr><th><input type="checkbox" id="leads-select-all"></th><th>Name</th><th>Email</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($leads as $lead) :
                                            $status = get_post_meta($lead->ID, '_saas_lead_status', true) ?: 'New';
                                            ?>
                                            <tr>
                                                <td><input type="checkbox" class="lead-checkbox" value="<?php echo $lead->ID; ?>"></td>
                                                <td><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_name', true)); ?></td>
                                                <td><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_email', true)); ?></td>
                                                <td><span class="pro-badge" style="background:<?php echo ($status==='New') ? 'var(--primary)' : 'var(--secondary)'; ?>"><?php echo esc_html($status); ?></span></td>
                                                <td><?php echo get_the_date('M j', $lead->ID); ?></td>
                                                <td><button class="view-lead button" data-id="<?php echo $lead->ID; ?>">View</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else : ?>
                                <p style="color:var(--text-muted);">No leads captured yet. Your funnel is ready to go!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div id="tab-analytics" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Performance</h3>
                        <div style="height: 280px; margin-bottom: 24px;"><canvas id="saas-analytics-chart"></canvas></div>
                        <?php $stats = $analytics->get_user_summary($user_id); ?>
                        <div class="stats-grid">
                            <div class="stat-card"><small>VIEWS</small><div class="value"><?php echo number_format($stats['views']); ?></div></div>
                            <div class="stat-card"><small>CLICKS</small><div class="value"><?php echo number_format($stats['clicks']); ?></div></div>
                            <div class="stat-card"><small>CONV. RATE</small><div class="value" style="color:var(--secondary);"><?php echo ($stats['views'] > 0) ? round(($stats['leads'] / $stats['views']) * 100, 1) : 0; ?>%</div></div>
                            <div class="stat-card"><small>LEADS</small><div class="value" style="color:var(--accent);"><?php echo number_format($stats['leads']); ?></div></div>
                        </div>

                        <div class="saas-insights-row" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-top:40px;">
                            <div class="insight-card">
                                <h5>Traffic Sources</h5>
                                <ul class="insight-list">
                                    <?php foreach($stats['referrers'] as $ref): ?>
                                        <li><span><?php echo esc_html($ref->referrer ?: 'Direct'); ?></span> <strong><?php echo $ref->count; ?></strong></li>
                                    <?php endforeach; ?>
                                    <?php if(empty($stats['referrers'])) echo '<li><small>No data yet</small></li>'; ?>
                                </ul>
                            </div>
                            <div class="insight-card">
                                <h5>Top Countries</h5>
                                <ul class="insight-list">
                                    <?php foreach($stats['countries'] as $c): ?>
                                        <li><span><?php echo esc_html($c->country_code); ?></span> <strong><?php echo $c->count; ?></strong></li>
                                    <?php endforeach; ?>
                                    <?php if(empty($stats['countries'])) echo '<li><small>No data yet</small></li>'; ?>
                                </ul>
                            </div>
                            <div class="insight-card">
                                <h5>Device Types</h5>
                                <ul class="insight-list">
                                    <?php foreach($stats['devices'] as $d): ?>
                                        <li><span><?php echo esc_html($d->label); ?></span> <strong><?php echo $d->count; ?></strong></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <div style="margin-top:40px;">
                            <h4>Top Performing Blocks</h4>
                            <div class="saas-table-wrapper">
                                <table class="saas-table">
                                    <thead><tr><th>Block</th><th>Type</th><th>Clicks</th><th>AB Result</th></tr></thead>
                                    <tbody>
                                        <?php
                                        $block_stats = $analytics->get_user_link_stats($user_id);
                                        foreach($links as $l) :
                                            $sid = $l->ID;
                                            $ca = isset($block_stats[$sid]) ? $block_stats[$sid]->clicks : 0;
                                            $cb = isset($block_stats[$sid]) ? $block_stats[$sid]->clicks_b : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html($l->post_title); ?></td>
                                                <td><small><?php echo get_post_meta($sid, '_saas_block_type', true); ?></small></td>
                                                <td><strong><?php echo $ca + $cb; ?></strong></td>
                                                <td><?php echo $cb > 0 ? "<small>A:$ca B:$cb</small>" : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-automation" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Settings & Rules</h3>
                        <form id="saas-automation-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <div class="field">
                                <label>Webhook URL (Zapier/Make)</label>
                                <input type="url" name="lead_webhook" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_webhook', true)); ?>">
                            </div>
                            <div class="field">
                                <label>Redirect after Submission</label>
                                <input type="url" name="lead_redirect" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_redirect', true)); ?>">
                            </div>
                            <button type="submit" class="btn-primary">Save Rules</button>
                        </form>
                    </div>
                </div>

                <div id="tab-integrations" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Third-Party Sync</h3>
                        <form id="saas-integrations-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <div class="field">
                                <label>Mailchimp API Key</label>
                                <div style="display:flex; gap:10px;">
                                    <input type="password" name="mailchimp_api" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_mailchimp_api', true)); ?>" style="flex:1;">
                                    <button type="button" class="button check-integration" data-platform="mailchimp">Test</button>
                                </div>
                            </div>
                            <div class="field">
                                <label>HubSpot Access Token</label>
                                <div style="display:flex; gap:10px;">
                                    <input type="password" name="hubspot_token" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_hubspot_token', true)); ?>" style="flex:1;">
                                    <button type="button" class="button check-integration" data-platform="hubspot">Test</button>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary">Save API Settings</button>
                        </form>
                        <p style="font-size:0.8rem; color:#888; margin-top:20px;">Connect your favorite CRM to sync leads automatically. Webhooks are also available in Settings. (Pro Feature)</p>
                    </div>
                </div>

                <div id="tab-referrals" class="saas-tab-content">
                    <div class="dashboard-card" style="background:var(--secondary-soft); border-color:var(--secondary);">
                        <h3 style="color:var(--secondary);">Affiliate Program</h3>
                        <p>Share your link and earn <strong>30% recurring commission</strong> on every user you refer.</p>

                        <div class="stats-grid" style="margin:20px 0;">
                            <div class="stat-card" style="background:#fff;"><small>TOTAL EARNED</small><div class="value" style="color:var(--secondary);">$0.00</div></div>
                            <div class="stat-card" style="background:#fff;"><small>ACTIVE REFS</small><div class="value">0</div></div>
                        </div>

                        <div style="background:#fff; padding:15px; border-radius:10px; border:1px dashed var(--secondary); margin-bottom:20px;">
                            <label style="display:block; font-size:0.7rem; color:var(--text-muted); margin-bottom:5px;">YOUR UNIQUE LINK</label>
                            <code style="font-weight:bold; word-break:break-all;"><?php echo home_url('/?ref=' . wp_get_current_user()->user_login); ?></code>
                        </div>
                        <button class="btn-primary" style="background:var(--secondary); width:100%;" onclick="alert('Referral Link Copied!')">Copy Referral Link</button>
                    </div>

                    <div class="dashboard-card">
                        <h4>Recent Payouts</h4>
                        <p style="color:var(--text-muted); font-size:0.9rem;">No payouts recorded yet. Start sharing to earn!</p>
                    </div>
                </div>

                <div id="tab-billing" class="saas-tab-content">
                    <div class="dashboard-card" style="text-align:center;">
                        <h3>Go Pro 🚀</h3>
                        <div class="plan-card" style="background:var(--primary-soft); padding:32px; border-radius:20px; border:2px solid var(--primary); max-width:320px; margin:20px auto;">
                            <h4 style="font-size:1.5rem; margin:0;">Elite Pro</h4>
                            <div style="font-size:2.5rem; font-weight:900; margin:16px 0;">$19<small style="font-size:1rem;">/mo</small></div>
                            <ul style="list-style:none; padding:0; margin-bottom: 24px; line-height:2; font-size: 0.9rem;">
                                <li>✓ Deep Analytics</li>
                                <li>✓ Pro Backgrounds</li>
                                <li>✓ No Branding</li>
                            </ul>
                            <?php if ($is_pro) : ?>
                                <button disabled class="btn-primary" style="width:100%;">Current Plan Active</button>
                            <?php else : ?>
                                <button id="saas-simulate-pro" class="btn-primary" style="width:100%;">Upgrade Now</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div> <!-- End Main Area -->

            <div class="saas-preview-pane">
                <div class="preview-header">
                    <strong>Real-Time Preview</strong>
                    <button onclick="document.getElementById('saas-preview-frame').contentWindow.location.reload();" class="button">🔄</button>
                </div>
                <div class="preview-frame-container">
                    <iframe id="saas-preview-frame" src="<?php echo home_url('/' . $profile_obj->post_name); ?>"></iframe>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <div id="saas-notif-modal" class="saas-modal">
            <div class="saas-modal-content" style="max-width:400px;">
                <span class="close-modal">&times;</span>
                <h3>Recent Activity</h3>
                <div id="notif-list" style="max-height:300px; overflow-y:auto;">
                    <div style="padding:12px; border-bottom:1px solid #eee;">🚀 Welcome to your new dashboard!</div>
                    <?php
                    $recent = get_posts(['post_type' => 'saas_lead', 'post_author' => $user_id, 'numberposts' => 5]);
                    foreach($recent as $r) : ?>
                        <div style="padding:12px; border-bottom:1px solid #eee; font-size:0.85rem;">
                            <strong>New Lead:</strong> <?php echo esc_html(get_post_meta($r->ID, '_saas_lead_name', true)); ?>
                            <br><small style="color:#888;"><?php echo get_the_date('', $r->ID); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="saas-wizard-modal" class="saas-modal">
            <div class="saas-modal-content" style="max-width:600px;">
                <span class="close-modal">&times;</span>
                <div class="wizard-step active" data-step="1">
                    <h3>Welcome! Let's build your profile 🚀</h3>
                    <p>What is your primary goal?</p>
                    <select id="wizard-niche" class="field">
                        <option value="coach">Capture Coaching Leads</option>
                        <option value="creator">Share Content & Links</option>
                        <option value="realtor">Real Estate Showcasing</option>
                        <option value="business">Business Networking</option>
                    </select>
                    <button class="btn-primary next-step" style="width:100%;">Next Step</button>
                </div>
                <div class="wizard-step" data-step="2">
                    <h3>Your Digital Identity</h3>
                    <div class="field"><label>Your Professional Headline</label><input type="text" id="wizard-headline" placeholder="e.g. Scaling Brands with Elite Strategy"></div>
                    <div class="field"><label>Short Bio</label><textarea id="wizard-bio" rows="3"></textarea></div>
                    <div style="display:flex; gap:10px;">
                        <button class="button prev-step" style="flex:1;">Back</button>
                        <button class="btn-primary next-step" style="flex:2;">Next Step</button>
                    </div>
                </div>
                <div class="wizard-step" data-step="3">
                    <h3>Launch Ready!</h3>
                    <p>Your profile is being optimized for your niche. Click finish to see your new dashboard.</p>
                    <button id="wizard-finish" class="btn-primary" style="width:100%;">Finish & Generate</button>
                </div>
                <div class="wizard-progress"><div class="progress-bar-fill"></div></div>
            </div>
        </div>

        <!-- Modals -->
        <div id="saas-lead-modal" class="saas-modal">
            <div class="saas-modal-content">
                <span class="close-modal">&times;</span>
                <h3>Lead Details</h3>
                <div id="lead-details-content" style="line-height:1.8;"></div>
            </div>
        </div>

        <div id="saas-edit-modal" class="saas-modal">
            <div class="saas-modal-content">
                <span class="close-modal">&times;</span>
                <h3>Modify Block</h3>
                <form id="saas-edit-link-form">
                    <input type="hidden" name="link_id" id="edit-link-id">

                    <div class="field"><label>Block Label</label><input type="text" name="title" id="edit-link-title" required></div>
                    <div class="field"><label>URL / Destination</label><input type="url" name="url" id="edit-link-url" required></div>
                    <div class="field"><label>Description / Extra Content</label><textarea name="extra" id="edit-link-extra" rows="3"></textarea></div>

                    <button type="button" class="button toggle-advanced" style="width:100%; margin-bottom:20px; background:#f1f5f9; color:#475569; font-weight:bold;">⚙️ Advanced Options</button>

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

                        <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                            <label>A/B Testing - Variant B Title (Pro)</label>
                            <input type="text" name="ab_title_b" id="edit-link-ab-title" placeholder="Alternate Title">
                        </div>

                        <div class="field">
                            <label>Visibility Scheduling</label>
                            <div style="display:flex; gap:10px;">
                                <input type="date" name="start_date" id="edit-link-start" style="flex:1;">
                                <input type="date" name="end_date" id="edit-link-end" style="flex:1;">
                            </div>
                        </div>

                        <div class="field">
                            <label>Hour Range (0-23)</label>
                            <div style="display:flex; gap:10px;">
                                <input type="number" name="hour_from" id="edit-link-hour-from" placeholder="From" min="0" max="23" style="flex:1;">
                                <input type="number" name="hour_to" id="edit-link-hour-to" placeholder="To" min="0" max="23" style="flex:1;">
                            </div>
                        </div>

                        <div class="field">
                            <label>Password Unlock</label>
                            <input type="text" name="link_password" id="edit-link-pass" placeholder="Block password">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;">Save All Changes</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
new Saas_Dashboard();
