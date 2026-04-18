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
        wp_enqueue_style( 'saas-dashboard-css', plugin_dir_url( __FILE__ ) . 'dashboard.css', [], '2.7' );
        wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], '1.15.0', true );
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.0.0', true );
        wp_enqueue_script( 'saas-dashboard-js', plugin_dir_url( __FILE__ ) . 'dashboard.js', [ 'jquery' ], '2.7', true );
        wp_localize_script( 'saas-dashboard-js', 'saas_dashboard_data', [
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'saas_dashboard_nonce' ),
            'templates' => get_option('saas_templates') ?: []
        ]);
    }

    public function render_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<p>Please log in to manage your profile.</p>';
        }

        $user_id = get_current_user_id();
        $all_user_profiles = get_posts([
            'post_type'   => 'saas_profile',
            'author'      => $user_id,
            'numberposts' => -1,
        ]);

        $active_profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;
        $payments = new Saas_Payments();

        // Explicit ownership check for security
        $profile_obj = null;
        if ($active_profile_id) {
            $test_post = get_post($active_profile_id);
            if ($test_post && $test_post->post_author == $user_id && $test_post->post_type === 'saas_profile') {
                $profile_obj = $test_post;
            }
        }

        if ( ! $profile_obj ) {
            if ( ! empty( $all_user_profiles ) ) {
                $profile_obj = $all_user_profiles[0];
            } else {
                $user = wp_get_current_user();
                $new_id = wp_insert_post([
                    'post_type'   => 'saas_profile',
                    'post_title'  => $user->display_name,
                    'post_name'   => $user->user_login,
                    'post_status' => 'publish',
                    'post_author' => $user_id,
                ]);
                $profile_obj = get_post($new_id);
            }
        }
        $profile_id = $profile_obj->ID;

        $meta = saas_get_profile_meta( $profile_id );
        $is_pro = saas_is_profile_licensed($profile_id);

        // Nudge for intended Pro/Agency users
        $target_plan = get_user_meta($user_id, '_saas_registration_target_plan', true);
        if ( ! $is_pro && in_array($target_plan, ['pro', 'agency']) ) {
            $plan_label = ($target_plan === 'agency') ? 'Agency Unlimited' : 'Elite Pro';
            echo '<div class="saas-onboarding-card dashboard-card" style="background:var(--accent); margin-bottom:20px; border:none;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <p style="margin:0; font-weight:700; color:#fff;">🌟 Ready to complete your '. $plan_label .' upgrade? Unlock all features now.</p>
                    <button class="button" onclick="window.location.search=\'?tab=billing\'">Complete Upgrade</button>
                </div>
            </div>';
        }

        $analytics = new Saas_Analytics();
        $link_stats = $analytics->get_user_link_stats($user_id);

        // Auto-launch Wizard for new profiles
        $show_wizard = empty($meta['headline']) && empty($links);

        $links = get_posts([
            'post_type'   => 'saas_link',
            'author'      => $user_id,
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
            <div class="saas-top-utility-nav" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <div style="display:flex; gap:20px;">
                    <a href="<?php echo home_url('/'); ?>" style="color:var(--text-muted); text-decoration:none; font-weight:700; font-size:0.85rem;">← Back to Website</a>
                    <a href="<?php echo home_url('/' . $profile_obj->post_name); ?>" target="_blank" style="color:var(--primary); text-decoration:none; font-weight:700; font-size:0.85rem;">🌍 View Live Profile</a>
                </div>
                <a href="<?php echo wp_logout_url(home_url()); ?>" style="color:var(--danger); text-decoration:none; font-weight:700; font-size:0.85rem;">Logout 👋</a>
            </div>
            <div class="dashboard-main-area">
                <!-- Onboarding Checklist -->
                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; margin-bottom:20px;">
                    <div class="saas-onboarding-card dashboard-card" style="margin-bottom:0;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                            <div>
                                <h4 style="margin:0;">🚀 Get Started Checklist</h4>
                                <div style="display:flex; gap:15px; margin-top:8px; font-size:0.8rem; flex-wrap:wrap;">
                                    <span><?php echo $meta['headline'] ? '[✓]' : '[ ]'; ?> Bio</span>
                                    <span><?php echo count($links) > 0 ? '[✓]' : '[ ]'; ?> Blocks</span>
                                    <span><?php echo $is_pro ? '[✓]' : '[ ]'; ?> Pro Upgrade</span>
                                </div>
                            </div>
                            <button class="button" onclick="document.getElementById('saas-wizard-modal').style.display='flex'">Launch Setup Wizard</button>
                        </div>
                    </div>

                    <div class="dashboard-card" style="margin-bottom:0; padding:15px; border-left: 4px solid var(--secondary);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <h4 style="margin:0; font-size:0.85rem; color:var(--text-muted);">Pulse: <?php echo date('F'); ?> Activity</h4>
                            <span class="pulse-dot"></span>
                        </div>
                        <div class="recent-activity-list" style="font-size:0.75rem;">
                            <?php
                            global $wpdb;
                            $table = $wpdb->prefix . 'saas_analytics';
                            // Combine analytics and recent leads
                            $recent_activity = [];

                            $events = $wpdb->get_results($wpdb->prepare("SELECT event_type as type, target_id, created_at FROM $table WHERE user_id = %d ORDER BY id DESC LIMIT 5", $user_id));
                            foreach($events as $e) {
                                $recent_activity[] = [
                                    'type' => $e->type,
                                    'target' => ($e->type === 'view') ? 'Profile' : get_the_title($e->target_id),
                                    'time' => strtotime($e->created_at),
                                    'icon' => ($e->type === 'view') ? '👁️' : '🖱️'
                                ];
                            }

                            $recent_leads = get_posts(['post_type' => 'saas_lead', 'author' => $user_id, 'numberposts' => 3]);
                            foreach($recent_leads as $rl) {
                                $recent_activity[] = [
                                    'type' => 'lead',
                                    'target' => get_post_meta($rl->ID, '_saas_lead_name', true),
                                    'time' => get_post_time('U', true, $rl),
                                    'icon' => '🚀'
                                ];
                            }

                            usort($recent_activity, function($a, $b) { return $b['time'] - $a['time']; });
                            $recent_activity = array_slice($recent_activity, 0, 4);

                            if ($recent_activity) :
                                foreach($recent_activity as $act) :
                                    ?>
                                    <div style="margin-bottom:8px; padding-bottom:8px; border-bottom:1px solid #f1f5f9; display:flex; gap:10px; align-items:start;">
                                        <span style="font-size:1rem;"><?php echo $act['icon']; ?></span>
                                        <div style="flex:1;">
                                            <strong><?php echo esc_html(ucfirst($act['type'])); ?></strong>: <?php echo esc_html($act['target']); ?>
                                            <br><span style="color:#94a3b8; font-size:0.7rem;"><?php echo human_time_diff($act['time'], current_time('timestamp')); ?> ago</span>
                                        </div>
                                    </div>
                                <?php endforeach;
                            else : ?>
                                <p style="color:#94a3b8; margin:0;">Waiting for first visitor...</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="saas-dashboard-header">
                    <div class="profile-switcher-wrapper">
                        <h2 class="profile-title"><?php echo esc_html($profile_obj->post_title); ?> ▾</h2>
                        <div class="profile-dropdown">
                            <?php foreach($all_user_profiles as $up) : ?>
                                <div class="dropdown-item-wrapper <?php echo ($up->ID == $active_profile_id) ? 'active' : ''; ?>">
                                    <a href="?profile_id=<?php echo $up->ID; ?>" class="dropdown-item"><?php echo esc_html($up->post_title); ?></a>
                                    <div style="display:flex; gap:5px;">
                                        <button class="clone-profile-btn" data-id="<?php echo $up->ID; ?>" title="Clone Profile">📋</button>
                                        <button class="delete-profile-btn" data-id="<?php echo $up->ID; ?>" title="Delete Profile" style="color:var(--danger);">🗑️</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <button id="saas-add-profile-trigger" class="add-profile-btn">+ New Profile</button>
                        </div>
                    </div>
                    <div class="saas-share-bar">
                        <?php
                        $new_leads_count = get_posts(['post_type' => 'saas_lead', 'author' => $user_id, 'meta_key' => '_saas_lead_status', 'meta_value' => 'New', 'fields' => 'ids', 'numberposts' => -1]);
                        $count = count($new_leads_count);
                        ?>
                        <div class="saas-notif-bell" onclick="document.getElementById('saas-notif-modal').style.display='flex'">🔔<?php if($count > 0) echo '<span class="notif-count">'.$count.'</span>'; ?></div>
                        <input type="text" id="saas-my-link" value="<?php echo home_url('/' . $profile_obj->post_name); ?>" readonly>
                        <button id="saas-copy-btn" class="btn-primary">Copy Link</button>
                        <button id="saas-preview-trigger" class="btn-primary" style="background:var(--secondary);">👁️ Preview</button>
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
                    <button data-tab="share">📱 Share</button>
                    <button data-tab="templates">🎨 Templates</button>
                    <button data-tab="billing">💳 Pro</button>
                    <button data-tab="seo">🔍 SEO</button>
                    <button data-tab="tracking">📊 Tracking</button>
                    <?php
                    $unread_msgs = get_posts([
                        'post_type' => 'saas_message',
                        'meta_query' => [
                            ['key' => '_saas_msg_recipient', 'value' => $user_id],
                            ['key' => '_saas_msg_status', 'value' => 'unread']
                        ],
                        'fields' => 'ids',
                        'numberposts' => -1
                    ]);
                    $msg_count = count($unread_msgs);
                    ?>
                    <button data-tab="inbox" style="position:relative;">📩 Inbox <?php if($msg_count > 0) echo '<span class="notif-count" style="top:-5px; right:-5px; font-size:0.6rem; padding:1px 4px;">'.$msg_count.'</span>'; ?></button>
                    <button data-tab="training">🎓 Training</button>
                    <button data-tab="account">👤 Account</button>
                </nav>

                <div id="tab-links" class="saas-tab-content active">
                    <div class="link-tab-grid">
                        <div class="block-picker-sidebar">
                            <div class="dashboard-card">
                                <h3>Manage Blocks</h3>
                                <p class="field-hint">Blocks are the building blocks of your funnel. Use them to share links, capture leads, or showcase testimonials.</p>
                                <div class="saas-block-picker">
                                    <div class="picker-item active" data-type="button"><span>🔗</span> Button</div>
                                    <div class="picker-item" data-type="video"><span>🎬</span> Video</div>
                                    <div class="picker-item" data-type="testimonial"><span>⭐</span> Testim</div>
                                    <div class="picker-item" data-type="faq"><span>❓</span> FAQ</div>
                                    <div class="picker-item" data-type="pricing"><span>💰</span> Price</div>
                                    <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="image_gallery"><span>🖼️</span> Gal <span class="pro-badge">Pro</span></div>
                                    <div class="picker-item" data-type="social_icons"><span>📱</span> Social</div>
                                    <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="newsletter"><span>📧</span> Mail <span class="pro-badge">Pro</span></div>
                                    <div class="picker-item" data-type="lead_form"><span>🎯</span> Form</div>
                                    <div class="picker-item <?php echo $is_pro ? '' : 'pro-locked'; ?>" data-type="calendar"><span>📅</span> Cal <span class="pro-badge">Pro</span></div>
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
                                $extra = get_post_meta($link->ID, '_saas_link_desc', true) ?: (get_post_meta($link->ID, '_saas_testimonial_text', true) ?: get_post_meta($link->ID, '_saas_faq_answer', true));
                                if (!$extra) {
                                    if ($type === 'pricing' || $type === 'product') {
                                        $price = get_post_meta($link->ID, '_saas_price', true);
                                        $feats = get_post_meta($link->ID, '_saas_features', true);
                                        if ($price) $extra = $price . ($feats ? "\n" . (is_array($feats) ? implode("\n", $feats) : $feats) : "");
                                    } elseif ($type === 'image_gallery') {
                                        $imgs = get_post_meta($link->ID, '_saas_gallery_images', true);
                                        if ($imgs) $extra = is_array($imgs) ? implode("\n", $imgs) : $imgs;
                                    } elseif ($type === 'social_icons') {
                                        $socials = get_post_meta($link->ID, '_saas_social_data', true);
                                        if ($socials && is_array($socials)) {
                                            $lines = [];
                                            foreach($socials as $p => $u) $lines[] = "$p:$u";
                                            $extra = implode("\n", $lines);
                                        }
                                    } elseif ($type === 'countdown') {
                                        $extra = get_post_meta($link->ID, '_saas_expiry', true);
                                    } elseif ($type === 'milestone') {
                                        $lbl = get_post_meta($link->ID, '_saas_ms_label', true);
                                        $per = get_post_meta($link->ID, '_saas_ms_percent', true);
                                        if ($lbl) $extra = "$lbl:$per";
                                    }
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
                                    data-link-image-id="<?php echo esc_attr(get_post_meta($link->ID, '_saas_link_image_id', true)); ?>"
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

                            <!-- Inline Edit Block Content -->
                            <div id="saas-edit-inline" class="dashboard-card saas-inline-container" style="display:none; margin-top:20px; border: 2px solid var(--primary);">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                    <div style="display:flex; align-items:center; gap:15px;">
                                        <h3 style="margin:0;">✏️ Edit Block</h3>
                                        <span id="edit-block-type-badge" class="pro-badge" style="background:#64748b; font-size:0.7rem; padding:4px 10px; border-radius:8px;">BUTTON</span>
                                    </div>
                                    <button type="button" class="close-inline button" data-target="saas-edit-inline">&times;</button>
                                </div>
                                <p class="field-hint" style="margin-bottom:20px;">Optimize this block for maximum conversion. Use the advanced options to add A/B testing or device-specific routing.</p>
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
                                            <label>A/B Testing (Pro)</label>
                                            <div style="display:flex; gap:10px;">
                                                <input type="text" name="ab_title_b" id="edit-link-ab-title" placeholder="Variant B Title" style="flex:1;">
                                                <input type="url" name="ab_url_b" id="edit-link-ab-url" placeholder="Variant B URL" style="flex:1;">
                                            </div>
                                        </div>

                                        <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                                            <label>Conditional Routing (Pro)</label>
                                            <div style="display:flex; flex-direction:column; gap:10px;">
                                                <input type="url" name="url_mobile" id="edit-link-url-mobile" placeholder="Mobile-only URL">
                                                <div style="display:flex; gap:10px;">
                                                    <input type="text" name="url_geo_country" id="edit-link-geo-country" placeholder="Country Code (e.g. US)" style="flex:1;">
                                                    <input type="url" name="url_geo" id="edit-link-url-geo" placeholder="Geo-specific URL" style="flex:1;">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="field">
                                            <label>Custom Design</label>
                                            <div style="display:flex; gap:10px;">
                                                <div style="flex:1;">
                                                    <small>Background</small>
                                                    <input type="color" name="custom_bg" id="edit-link-custom-bg" style="height:40px; padding:2px;">
                                                </div>
                                                <div style="flex:1;">
                                                    <small>Text</small>
                                                    <input type="color" name="custom_text" id="edit-link-custom-text" style="height:40px; padding:2px;">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="field">
                                            <label>Visibility Scheduling</label>
                                            <div style="display:flex; gap:10px;">
                                                <input type="date" name="start_date" id="edit-link-start" style="flex:1;" title="Start Date">
                                                <input type="date" name="end_date" id="edit-link-end" style="flex:1;" title="End Date">
                                            </div>
                                            <p class="field-hint">Automate your promotions. This block will only be visible between these dates.</p>
                                        </div>

                                        <div class="field">
                                            <label>Hour Range (0-23)</label>
                                            <div style="display:flex; gap:10px;">
                                                <input type="number" name="hour_from" id="edit-link-hour-from" placeholder="From" min="0" max="23" style="flex:1;">
                                                <input type="number" name="hour_to" id="edit-link-hour-to" placeholder="To" min="0" max="23" style="flex:1;">
                                            </div>
                                        </div>

                                            <div class="field">
                                                <label>Icon/Thumb Image</label>
                                                <div id="edit-link-image-preview" style="width:60px; height:60px; border-radius:10px; background:#eee; margin-bottom:10px; overflow:hidden; border:1px solid #ddd;"></div>
                                                <input type="hidden" name="link_image_id" id="edit-link-image-id">
                                                <button type="button" class="button select-media" data-target="link-image">Select Icon</button>
                                            </div>

                                        <div class="field">
                                            <label>Password Unlock</label>
                                            <input type="text" name="link_password" id="edit-link-pass" placeholder="Block password">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn-primary" style="width:100%;">Save All Changes</button>
                                </form>
                            </div>

                            <!-- Inline Real-Time Preview -->
                            <div id="saas-preview-inline" class="dashboard-card saas-inline-container" style="display:none; margin-top:20px; border: 2px solid var(--secondary);">
                                <div class="preview-header" style="width:100%; color:var(--text-dark); margin-bottom:20px;">
                                    <h3 style="margin:0;">📱 Real-Time Preview</h3>
                                    <div style="display:flex; gap:10px;">
                                        <button onclick="document.getElementById('saas-preview-frame').contentWindow.location.reload();" class="button">🔄 Refresh</button>
                                        <button type="button" class="close-inline button" data-target="saas-preview-inline">&times;</button>
                                    </div>
                                </div>
                                <div class="preview-frame-container" style="margin: 0 auto;">
                                    <iframe id="saas-preview-frame" src="<?php echo home_url('/' . $profile_obj->post_name . '?preview=1'); ?>"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-profile" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Identity Settings</h3>
                        <form id="saas-profile-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <input type="hidden" name="form_context" value="profile">

                            <div style="display:flex; gap:30px; margin-bottom:30px; align-items:center;">
                                <div class="image-select-wrapper" style="text-align:center;">
                                    <label>Profile Image</label>
                                    <div id="profile-image-preview" class="image-preview-circle" style="width:100px; height:100px; border-radius:50%; background:#eee; margin:10px auto; overflow:hidden; border:2px solid var(--border); cursor:pointer;">
                                        <?php if ( has_post_thumbnail( $profile_id ) ) : ?>
                                            <?php echo get_the_post_thumbnail( $profile_id, 'thumbnail', ['style' => 'width:100%; height:100%; object-fit:cover;'] ); ?>
                                        <?php else : ?>
                                            <span style="line-height:100px; color:#aaa; font-size:2rem;">+</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="profile_image_id" id="profile-image-id" value="<?php echo get_post_thumbnail_id($profile_id); ?>">
                                    <button type="button" class="button select-media" data-target="profile-image">Change</button>
                                </div>

                                <div class="image-select-wrapper" style="flex:1;">
                                    <label>Cover Banner</label>
                                    <?php $cover_id = get_post_meta($profile_id, '_saas_cover_id', true); ?>
                                    <div id="cover-image-preview" class="image-preview-rect" style="width:100%; height:100px; border-radius:12px; background:#eee; margin:10px 0; overflow:hidden; border:2px solid var(--border); cursor:pointer;">
                                        <?php if ( $cover_id ) : ?>
                                            <?php echo wp_get_attachment_image( $cover_id, 'medium', false, ['style' => 'width:100%; height:100%; object-fit:cover;'] ); ?>
                                        <?php else : ?>
                                            <span style="display:block; text-align:center; line-height:100px; color:#aaa;">Upload Banner</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="cover_image_id" id="cover-image-id" value="<?php echo $cover_id; ?>">
                                    <button type="button" class="button select-media" data-target="cover-image">Select Banner</button>
                                </div>
                            </div>

                            <div class="field">
                                <label>Vanity URL (Username)</label>
                                <div style="display:flex; align-items:center; background:var(--bg-main); border:1px solid var(--border); border-radius:12px; padding:0 15px;">
                                    <span style="color:var(--text-muted); font-weight:700;"><?php echo parse_url(home_url(), PHP_URL_HOST); ?>/</span>
                                    <input type="text" name="profile_slug" value="<?php echo esc_attr($profile_obj->post_name); ?>" style="border:none; background:transparent; padding:12px 5px; flex:1; font-weight:700;">
                                </div>
                                <p style="font-size:0.7rem; color:var(--text-muted); margin-top:5px;">Changing this will break your old links. Use with caution.</p>
                            </div>

                            <div class="field">
                                <label>Profile Headline</label>
                                <div style="display:flex; gap:10px;">
                                    <input type="text" name="headline" value="<?php echo esc_attr( $meta['headline'] ); ?>" style="flex:1;" placeholder="e.g. Scaling Founders from 6 to 7 Figures">
                                    <button type="button" class="ai-assist-btn button" data-target="headline" title="AI Generate Headline">✨</button>
                                </div>
                                <p class="field-hint"><strong>Pro Tip:</strong> Focus on the <em>result</em> you provide, not just your title.</p>
                            </div>
                            <div class="field">
                                <label>Short Biography</label>
                                <textarea name="bio" rows="4" placeholder="Briefly describe your expertise and how you help clients..."><?php echo esc_textarea( $meta['bio'] ); ?></textarea>
                                <p class="field-hint">Use 2-3 sentences to build authority and trust quickly.</p>
                            </div>

                            <div class="field">
                                <label>Social Links (for vCard & Discovery)</label>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                    <input type="url" name="social_links[twitter]" value="<?php echo esc_url($meta['social_links']['twitter'] ?? ''); ?>" placeholder="𝕏 / Twitter URL">
                                    <input type="url" name="social_links[linkedin]" value="<?php echo esc_url($meta['social_links']['linkedin'] ?? ''); ?>" placeholder="LinkedIn URL">
                                    <input type="url" name="social_links[instagram]" value="<?php echo esc_url($meta['social_links']['instagram'] ?? ''); ?>" placeholder="Instagram URL">
                                    <input type="url" name="social_links[youtube]" value="<?php echo esc_url($meta['social_links']['youtube'] ?? ''); ?>" placeholder="YouTube URL">
                                </div>
                            </div>
                            <div class="field">
                                <label>Your Niche / Category</label>
                                <select name="niche" id="profile-niche">
                                    <?php
                                    $niche = get_post_meta($profile_id, '_saas_niche', true);
                                    $niches = [
                                        'servant' => 'Public Servant / Official',
                                        'coach' => 'Business Coach',
                                        'creator' => 'Digital Creator',
                                        'realtor' => 'Real Estate Pro',
                                        'business' => 'Corporate Entity',
                                        'speaker' => 'Public Speaker',
                                        'author' => 'Author / Writer',
                                        'consultant' => 'Strategy Consultant',
                                        'lawyer' => 'Lawyer / Legal',
                                        'doctor' => 'Doctor / Healthcare',
                                        'artist' => 'Artist / Designer',
                                        'agency' => 'Agency Owner',
                                        'freelancer' => 'Creative Freelancer',
                                        'tiktok' => 'Influencer / TikTok',
                                        'luxury' => 'Luxury Advisory'
                                    ];
                                    foreach($niches as $k => $v) : ?>
                                        <option value="<?php echo $k; ?>" <?php selected($niche, $k); ?>><?php echo $v; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                                <div class="field">
                                    <label>Company / Organization</label>
                                    <input type="text" name="company" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_company', true)); ?>">
                                </div>
                                <div class="field">
                                    <label>Public Phone (for vCard)</label>
                                    <input type="text" name="phone" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_phone', true)); ?>" placeholder="+1 234 567 890">
                                </div>
                            </div>
                            <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                                <label>Custom Domain / Subdomain (Pro)</label>
                                <input type="text" name="custom_domain" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_custom_domain', true)); ?>" placeholder="profile.yourdomain.com">
                                <p style="font-size:0.7rem; color:var(--text-muted); margin-top:5px;">Point your CNAME record to our server to use your own domain.</p>
                            </div>

                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                                <div class="field">
                                    <label><input type="checkbox" name="show_in_directory" value="1" <?php checked(get_post_meta($profile_id, '_saas_show_in_directory', true), 1); ?>> Show in Directory</label>
                                </div>
                                <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                                    <label><input type="checkbox" name="verified_badge" value="1" <?php checked(get_post_meta($profile_id, '_saas_verified_badge', true), 1); ?>> Verified Badge (Pro)</label>
                                </div>
                            </div>

                            <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                                <label>Profile Password Protection (Pro)</label>
                                <input type="text" name="profile_password" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_profile_password', true)); ?>" placeholder="Leave blank for public access">
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
                            <input type="hidden" name="form_context" value="branding">

                            <div class="field">
                                <label>Base Theme</label>
                                <select name="profile_theme" id="profile-theme-select">
                                    <option value="light" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'light'); ?>>Light Mode (Clean & Minimal)</option>
                                    <option value="dark" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'dark'); ?>>Dark Mode (Modern & Bold)</option>
                                    <option value="vibrant" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'vibrant'); ?>>Vibrant (Creative & Energetic)</option>
                                    <option value="luxury" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'luxury'); ?>>Luxury (Elite & Premium)</option>
                                </select>
                                <p class="field-hint"><strong>Recommendation:</strong> Use "Luxury" if you sell high-ticket services ($2,000+).</p>
                            </div>

                            <div class="field" id="saas-bg-value-wrapper">
                                <label id="saas-bg-value-label">Background Value</label>
                                <input type="text" name="bg_value" id="saas-bg-value-input" value="<?php echo esc_attr($profile_bg_val ?: '#f3f3f1'); ?>">
                                <p style="font-size:0.7rem; color:#888; margin-top:5px;">Hex color (e.g. #ffffff) or CSS gradient.</p>
                            </div>

                            <div class="field">
                                <label>Card Elevation (Shadow)</label>
                                <select name="container_shadow">
                                    <option value="soft" <?php selected(get_post_meta($profile_id, '_saas_container_shadow', true), 'soft'); ?>>Soft Glow</option>
                                    <option value="hard" <?php selected(get_post_meta($profile_id, '_saas_container_shadow', true), 'hard'); ?>>Hard Edge (Brutalism)</option>
                                    <option value="none" <?php selected(get_post_meta($profile_id, '_saas_container_shadow', true), 'none'); ?>>Flat (Minimalist)</option>
                                </select>
                            </div>

                            <div class="field">
                                <label>Typography</label>
                                <select name="font_family">
                                    <option value="'Inter', sans-serif" <?php selected(get_post_meta($profile_id, '_saas_font_family', true), "'Inter', sans-serif"); ?>>Inter (Modern)</option>
                                    <option value="'Montserrat', sans-serif" <?php selected(get_post_meta($profile_id, '_saas_font_family', true), "'Montserrat', sans-serif"); ?>>Montserrat (Bold)</option>
                                    <option value="'Playfair Display', serif" <?php selected(get_post_meta($profile_id, '_saas_font_family', true), "'Playfair Display', serif"); ?>>Playfair (Elegant)</option>
                                </select>
                            </div>

                            <div class="field">
                                <label><input type="checkbox" name="social_proof" value="1" <?php checked(get_post_meta($profile_id, '_saas_social_proof', true), 1); ?>> Show Social Proof Bubble (e.g. "100 people viewed")</label>
                            </div>

                            <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                                <label><input type="checkbox" name="hide_branding" value="1" <?php checked(get_post_meta($profile_id, '_saas_hide_branding', true), 1); ?>> Hide "Powered by" Branding (Pro)</label>
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
                            <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                                <label>Custom CSS (Pro)</label>
                                <textarea name="custom_css" rows="6" placeholder="/* Custom styles for your profile */" style="font-family:monospace; font-size:0.8rem;"><?php echo esc_textarea(get_post_meta($profile_id, '_saas_custom_css', true)); ?></textarea>
                            </div>

                            <button type="submit" class="btn-primary">Apply Styles</button>
                        </form>
                    </div>
                </div>

                <div id="tab-leads" class="saas-tab-content">
                    <div class="dashboard-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                            <h3>Captured Leads</h3>
                            <div style="display:flex; gap:10px;">
                                <input type="text" id="lead-search" placeholder="Search leads..." class="button" style="background:#fff; text-align:left;">
                                <a href="<?php echo admin_url('admin-ajax.php?action=saas_export_leads&security='.wp_create_nonce('saas_export_nonce')); ?>" class="button">📥 Export CSV</a>
                            </div>
                        </div>
                        <div class="saas-table-wrapper">
                            <?php
                            $leads = get_posts(['post_type' => 'saas_lead', 'author' => $user_id, 'numberposts' => 50]);
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
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                            <h3>Performance</h3>
                            <a href="<?php echo admin_url('admin-ajax.php?action=saas_export_analytics&security='.wp_create_nonce('saas_export_nonce')); ?>" class="button">📥 Export Stats</a>
                        </div>
                        <div style="height: 280px; margin-bottom: 24px;"><canvas id="saas-analytics-chart"></canvas></div>
                        <?php
                        $user_activity = $analytics->get_user_activity_over_time($user_id);
                        $u_labels = array_column($user_activity, 'date');
                        $u_views = array_column($user_activity, 'views');
                        $u_clicks = array_column($user_activity, 'clicks');
                        ?>
                        <script>
                            var saas_chart_data = {
                                labels: <?php echo json_encode($u_labels); ?>,
                                views: <?php echo json_encode($u_views); ?>,
                                clicks: <?php echo json_encode($u_clicks); ?>
                            };
                        </script>

                        <div class="saas-ab-testing-results" style="margin-bottom: 40px;">
                            <h4>A/B Testing Insights</h4>
                            <div style="height: 200px;"><canvas id="saas-ab-chart"></canvas></div>
                            <?php
                            $total_a = 0; $total_b = 0;
                            foreach($link_stats as $ls) {
                                $total_a += $ls->clicks;
                                $total_b += $ls->clicks_b;
                            }
                            ?>
                            <script>
                                var saas_ab_data = { a: <?php echo $total_a; ?>, b: <?php echo $total_b; ?> };
                            </script>
                        </div>

                        <?php $stats = $analytics->get_user_summary($user_id); ?>
                        <div class="stats-grid">
                            <div class="stat-card"><small>VIEWS</small><div class="value"><?php echo number_format($stats['views']); ?></div></div>
                            <div class="stat-card"><small>CLICKS</small><div class="value"><?php echo number_format($stats['clicks']); ?></div></div>
                            <div class="stat-card"><small>NFC TAPS</small><div class="value" style="color:var(--primary);"><?php echo number_format($stats['nfc']); ?></div></div>
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

                        <div style="margin-top:40px;">
                            <h4>Revenue & Orders</h4>
                            <div class="stats-grid" style="margin-bottom:20px;">
                                <div class="stat-card" style="background:var(--secondary-soft);">
                                    <small>TOTAL REVENUE</small>
                                    <?php
                                    $total_rev = 0;
                                    $all_orders = get_posts(['post_type' => 'saas_order', 'author' => $user_id, 'meta_key' => '_saas_order_status', 'meta_value' => 'completed', 'numberposts' => -1]);
                                    foreach($all_orders as $o) $total_rev += floatval(get_post_meta($o->ID, '_saas_order_amount', true));
                                    ?>
                                    <div class="value" style="color:var(--secondary);">$<?php echo number_format($total_rev, 2); ?></div>
                                </div>
                                <div class="stat-card">
                                    <small>COMPLETED SALES</small>
                                    <div class="value"><?php echo count($all_orders); ?></div>
                                </div>
                            </div>
                            <div class="saas-table-wrapper">
                                <table class="saas-table">
                                    <thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php
                                        $recent_orders = get_posts(['post_type' => 'saas_order', 'author' => $user_id, 'numberposts' => 10]);
                                        if ($recent_orders) :
                                            foreach($recent_orders as $o) :
                                                $amt = get_post_meta($o->ID, '_saas_order_amount', true);
                                                $st = get_post_meta($o->ID, '_saas_order_status', true);
                                                ?>
                                                <tr>
                                                    <td><?php echo get_the_date('M j', $o->ID); ?></td>
                                                    <td><?php echo esc_html($o->post_title); ?></td>
                                                    <td>$<?php echo number_format($amt, 2); ?></td>
                                                    <td><span class="pro-badge" style="background:<?php echo ($st==='completed') ? 'var(--secondary)' : '#94a3b8'; ?>"><?php echo ucfirst($st); ?></span></td>
                                                </tr>
                                            <?php endforeach;
                                        else : ?>
                                            <tr><td colspan="4" style="text-align:center; color:#999;">No sales recorded yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-share" class="saas-tab-content">
                    <div class="dashboard-card" style="text-align:center;">
                        <h3>Share Your Identity</h3>
                        <div style="margin:20px 0;">
                            <img src="<?php echo saas_get_profile_qr_url($profile_obj->post_name, get_post_meta($profile_id, '_saas_qr_color', true) ?: '#000000'); ?>" style="max-width:200px; border-radius:15px; border:5px solid #fff; box-shadow:var(--shadow);">
                        </div>
                        <p>Download your custom QR code for business cards and marketing materials.</p>

                        <form id="saas-qr-form" style="max-width:300px; margin:0 auto 20px;">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <input type="hidden" name="form_context" value="qr">
                            <div class="field">
                                <label>QR Code Color</label>
                                <input type="color" name="qr_color" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_qr_color', true) ?: '#000000'); ?>" onchange="$(this).closest('form').submit()">
                            </div>
                        </form>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; max-width:400px; margin:20px auto;">
                            <a href="<?php echo home_url('/?saas_action=vcard&profile='.$profile_id); ?>" class="button" style="width:100%;">📥 Get vCard</a>
                            <button class="button" onclick="window.print()">🖨️ Print Card</button>
                        </div>
                        <hr>
                        <h4>Social Story Card (Elite Pro)</h4>
                        <div id="saas-story-card-preview" style="width:240px; height:426px; background:linear-gradient(135deg, <?php echo $meta['theme_color']; ?> 0%, #000 100%); margin:20px auto; border-radius:20px; position:relative; padding:30px; color:#fff; overflow:hidden; display: flex; flex-direction: column;">
                             <div style="text-align:center;">
                                 <?php if ( has_post_thumbnail( $profile_id ) ) : ?>
                                    <?php echo get_the_post_thumbnail( $profile_id, 'thumbnail', ['style' => 'width:80px; height:80px; border-radius:50%; border:3px solid #fff;']); ?>
                                 <?php endif; ?>
                                 <h3 style="margin:10px 0 5px; font-size:1.2rem;"><?php echo esc_html($profile_obj->post_title); ?></h3>
                                 <p style="font-size:0.7rem; opacity:0.8;"><?php echo esc_html($meta['headline']); ?></p>
                             </div>
                             <div style="margin-top: auto; padding-bottom: 40px; text-align:center;">
                                <img src="<?php echo saas_get_profile_qr_url($profile_obj->post_name, '#ffffff'); ?>" style="width:100px; border-radius:10px;">
                                <p style="font-size:0.8rem; margin-top:10px; font-weight:bold;">Scan to Connect</p>
                             </div>
                        </div>
                        <a href="<?php echo home_url('/story-card?profile_id='.$profile_id); ?>" target="_blank" class="button">📥 View & Download Story Card</a>

                        <hr>
                        <h4>NFC Configuration</h4>
                        <p style="font-size:0.8rem; color:var(--text-muted);">Program your NFC tag to point to: <br><code><?php echo home_url('/' . $profile_obj->post_name . '?src=nfc'); ?></code></p>
                    </div>
                </div>

                <div id="tab-tracking" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Tracking & Pixels</h3>
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:20px;">Add Google Analytics, Facebook Pixel, or custom tracking scripts. (Elite Pro Feature)</p>
                        <form id="saas-tracking-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <input type="hidden" name="form_context" value="tracking">
                            <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                                <label>Header Scripts (e.g. Google Tag Manager)</label>
                                <textarea name="header_scripts" rows="5" placeholder="<script>...</script>"><?php echo esc_textarea(get_post_meta($profile_id, '_saas_header_scripts', true)); ?></textarea>
                            </div>
                            <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                                <label>Footer Scripts (e.g. Conversion Pixels)</label>
                                <textarea name="footer_scripts" rows="5" placeholder="<script>...</script>"><?php echo esc_textarea(get_post_meta($profile_id, '_saas_footer_scripts', true)); ?></textarea>
                            </div>
                            <button type="submit" class="btn-primary">Save Scripts</button>
                        </form>
                    </div>
                </div>

                <div id="tab-seo" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Search Engine Optimization</h3>
                        <form id="saas-seo-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <input type="hidden" name="form_context" value="seo">
                            <div class="field">
                                <label>Meta Title</label>
                                <input type="text" name="meta_title" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_seo_title', true)); ?>" placeholder="Example: John Doe | Digital Marketing Consultant">
                            </div>
                            <div class="field">
                                <label>Meta Description</label>
                                <textarea name="meta_desc" rows="3" placeholder="A short summary of your profile for search engines."><?php echo esc_textarea(get_post_meta($profile_id, '_saas_seo_desc', true)); ?></textarea>
                            </div>
                            <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                                <label>Custom Favicon URL (Pro)</label>
                                <input type="url" name="favicon" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_favicon', true)); ?>" placeholder="https://yoursite.com/favicon.ico">
                            </div>

                            <div style="background:#f8fafc; padding:20px; border-radius:15px; border:1px solid #e2e8f0; margin-bottom:20px;">
                                <h4 style="margin:0 0 10px; font-size:0.9rem; color:#64748b;">Google Search Preview</h4>
                                <div style="color:#1a0dab; font-size:1.2rem; margin-bottom:2px;"><?php echo get_post_meta($profile_id, '_saas_seo_title', true) ?: $profile_obj->post_title; ?></div>
                                <div style="color:#006621; font-size:0.9rem; margin-bottom:5px;"><?php echo home_url('/' . $profile_obj->post_name); ?></div>
                                <div style="color:#545454; font-size:0.85rem; line-height:1.4;"><?php echo get_post_meta($profile_id, '_saas_seo_desc', true) ?: 'Check out my digital identity and conversion funnel.'; ?></div>
                            </div>

                            <button type="submit" class="btn-primary">Save SEO Settings</button>
                        </form>
                    </div>
                </div>

                <div id="tab-automation" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Settings & Rules</h3>
                        <form id="saas-automation-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <input type="hidden" name="form_context" value="automation">

                            <h4>Lead Form Customization</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                                <div>
                                    <div class="field">
                                        <label><input type="checkbox" name="form_field_phone" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_phone', true), 1); ?>> Enable Phone Field</label>
                                    </div>
                                    <div class="field">
                                        <label>Phone Label</label>
                                        <input type="text" name="form_label_phone" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_form_label_phone', true) ?: 'Phone Number'); ?>">
                                    </div>
                                    <div class="field">
                                        <label><input type="checkbox" name="form_req_phone" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_req_phone', true), 1); ?>> Phone Required</label>
                                    </div>
                                </div>
                                <div>
                                    <div class="field">
                                        <label><input type="checkbox" name="form_field_msg" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_msg', true), 1); ?>> Enable Message Field</label>
                                    </div>
                                    <div class="field">
                                        <label>Message Label</label>
                                        <input type="text" name="form_label_msg" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_form_label_msg', true) ?: 'Your Message'); ?>">
                                    </div>
                                    <div class="field">
                                        <label><input type="checkbox" name="form_req_msg" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_req_msg', true), 1); ?>> Message Required</label>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label>Success Message</label>
                                <input type="text" name="lead_success_msg" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_lead_success_msg', true) ?: 'Thank you! We will be in touch soon.'); ?>">
                            </div>

                            <hr>
                            <h4>Advanced Triggers</h4>
                            <div class="field <?php echo $payments->is_agency_user($user_id) ? '' : 'pro-gated-inline'; ?>" data-tier="agency">
                                <label>Webhook URL (Zapier/Make) <span class="pro-badge" style="background:#0f172a; color:#f59e0b; border:1px solid #f59e0b;">Agency</span></label>
                                <input type="url" name="lead_webhook" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_webhook', true)); ?>" <?php echo $payments->is_agency_user($user_id) ? '' : 'readonly'; ?>>
                                <p class="field-hint">Automatically send new leads to Zapier, Make, or your own API.</p>
                            </div>
                            <div class="field">
                                <label>Redirect after Submission</label>
                                <input type="url" name="lead_redirect" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_redirect', true)); ?>">
                            </div>
                            <div class="field">
                                <label>Lead Magnet URL (Auto-download)</label>
                                <input type="url" name="lead_magnet_url" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_magnet_url', true)); ?>">
                            </div>
                            <div class="field">
                                <label><input type="checkbox" name="lead_auto_respond" value="1" <?php checked(get_post_meta($profile_id, '_saas_lead_auto_respond', true), 1); ?>> Enable Email Auto-responder</label>
                            </div>
                            <div class="field">
                                <label>Auto-reply Message</label>
                                <textarea name="lead_auto_msg" rows="3"><?php echo esc_textarea(get_post_meta($profile_id, '_saas_lead_auto_msg', true)); ?></textarea>
                            </div>
                            <div style="display:flex; gap:10px; margin-bottom:20px;">
                                <button type="submit" class="btn-primary" style="flex:2;">Save Rules</button>
                                <button type="button" id="saas-test-webhook-btn" class="button" style="flex:1;">Test Webhook</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="tab-integrations" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Third-Party Sync</h3>
                        <form id="saas-integrations-form">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <input type="hidden" name="form_context" value="integrations">
                            <div class="field">
                                <label>Mailchimp API Key</label>
                                <div style="display:flex; gap:10px;">
                                    <input type="password" name="mailchimp_api" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_mailchimp_api', true)); ?>" style="flex:1;" placeholder="Paste your API key here">
                                    <button type="button" class="button check-integration" data-platform="mailchimp">Test Connection</button>
                                </div>
                                <p class="field-hint">Automatically sync new leads to your Mailchimp audience. Found in Account > Extras > API keys.</p>
                            </div>
                            <div class="field">
                                <label>Mailchimp Audience ID (List ID)</label>
                                <input type="text" name="mailchimp_list" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_mailchimp_list', true)); ?>" placeholder="e.g. 1a2b3c4d5e">
                                <p class="field-hint">The unique ID for your subscriber list.</p>
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
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="color:var(--secondary); margin:0;">Affiliate Program</h3>
                            <span class="pro-badge" style="background:var(--secondary);">ACTIVE</span>
                        </div>
                        <p style="margin-top:15px; line-height:1.6;">I built this tool to help consultants, and I want to reward you for spreading the word. Share your unique referral link and earn <strong><?php echo get_option('saas_affiliate_percentage') ?: 30; ?>% recurring commission</strong> for the lifetime of every user you refer.</p>

                        <?php
                        $earned = get_user_meta($user_id, '_saas_affiliate_earned', true) ?: 0;
                        $refs_count = count(get_users(['meta_key' => '_saas_referred_by', 'meta_value' => $user_id, 'fields' => 'ID']));
                        $marketing_materials = get_option('saas_marketing_materials') ?: [
                            ['name' => 'Standard Banner', 'img' => 'https://via.placeholder.com/300x100?text=Claim+Your+Elite+Bio', 'size' => '300x100'],
                            ['name' => 'Sidebar Ad', 'img' => 'https://via.placeholder.com/150x150?text=Stop+Losing+Leads', 'size' => '150x150']
                        ];
                        ?>
                        <div class="stats-grid" style="margin:20px 0;">
                            <div class="stat-card" style="background:#fff;"><small>TOTAL EARNED</small><div class="value" style="color:var(--secondary);">$<?php echo number_format($earned, 2); ?></div></div>
                            <div class="stat-card" style="background:#fff;"><small>ACTIVE REFS</small><div class="value"><?php echo $refs_count; ?></div></div>
                        </div>

                        <div style="background:#fff; padding:15px; border-radius:10px; border:1px dashed var(--secondary); margin-bottom:20px;">
                            <label style="display:block; font-size:0.7rem; color:var(--text-muted); margin-bottom:5px;">YOUR UNIQUE LINK</label>
                            <input type="text" id="saas-ref-link" value="<?php echo home_url('/?ref=' . wp_get_current_user()->user_login); ?>" readonly style="width:100%; border:none; font-weight:bold; background:transparent;">
                        </div>
                        <button id="saas-copy-ref-btn" class="btn-primary" style="background:var(--secondary); width:100%;">Copy Referral Link</button>

                        <div style="margin-top:30px; padding-top:20px; border-top:1px solid rgba(16, 185, 129, 0.2);">
                            <h4 style="color:var(--secondary); margin-bottom:15px;">Your Assigned Discount Codes</h4>
                            <?php
                            $all_coupons = get_option('saas_affiliate_coupons') ?: [];
                            $my_coupons = array_filter($all_coupons, function($c) use ($user_id) { return $c['user_id'] == $user_id; });
                            if ($my_coupons) : ?>
                                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:10px;">
                                    <?php foreach($my_coupons as $mc) : ?>
                                        <div style="background:#fff; padding:12px; border-radius:12px; text-align:center; box-shadow:0 4px 6px rgba(0,0,0,0.05);">
                                            <code style="display:block; font-size:1.1rem; color:var(--secondary); font-weight:900; margin-bottom:5px;"><?php echo esc_html($mc['code']); ?></code>
                                            <span style="font-size:0.7rem; font-weight:700; color:var(--text-muted);"><?php echo $mc['discount']; ?>% Discount</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p style="font-size:0.85rem; color:var(--text-muted);">Ask the admin to assign you a custom coupon code to share with your audience!</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <h4 style="color:var(--secondary); margin-bottom:15px;">Affiliate Earning History</h4>
                        <?php
                        $commissions = get_posts(['post_type' => 'saas_commission', 'author' => $user_id, 'numberposts' => 20]);
                        if($commissions) : ?>
                            <table class="saas-table">
                                <thead><tr><th>Date</th><th>Type</th><th>Order Amount</th><th>Your Commission</th></tr></thead>
                                <tbody>
                                    <?php foreach($commissions as $c) :
                                        $order_amt = get_post_meta($c->ID, '_saas_order_amount', true);
                                        $comm_amt  = get_post_meta($c->ID, '_saas_commission_amount', true);
                                        $perc      = get_post_meta($c->ID, '_saas_percentage', true);
                                        ?>
                                        <tr>
                                            <td><?php echo get_the_date('M j, Y', $c->ID); ?></td>
                                            <td><small>Recurring (<?php echo $perc; ?>%)</small></td>
                                            <td>$<?php echo number_format($order_amt, 2); ?></td>
                                            <td style="color:var(--secondary); font-weight:800;">+$<?php echo number_format($comm_amt, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color:var(--text-muted); font-size:0.9rem;">No commissions earned yet. Share your link to start earning!</p>
                        <?php endif; ?>
                    </div>

                    <div class="dashboard-card">
                        <h4>Recent Payouts</h4>
                        <?php
                        $payouts = get_posts(['post_type' => 'saas_payout', 'author' => $user_id, 'numberposts' => 10]);
                        if($payouts) : ?>
                            <table class="saas-table">
                                <thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach($payouts as $p) :
                                        $p_status = get_post_meta($p->ID, '_status', true) ?: 'pending';
                                        ?>
                                        <tr>
                                            <td><?php echo get_the_date('', $p->ID); ?></td>
                                            <td>$<?php echo number_format(get_post_meta($p->ID, '_amount', true), 2); ?></td>
                                            <td><span class="pro-badge" style="background:<?php echo ($p_status==='paid') ? 'var(--secondary)' : '#94a3b8'; ?>"><?php echo strtoupper($p_status); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color:var(--text-muted); font-size:0.9rem;">No payouts recorded yet.</p>
                        <?php endif; ?>
                    </div>

                    <div class="dashboard-card">
                        <h4>Referred Users</h4>
                        <?php
                        $referred_users = get_users(['meta_key' => '_saas_referred_by', 'meta_value' => $user_id, 'number' => 10]);
                        if($referred_users) : ?>
                            <table class="saas-table">
                                <thead><tr><th>User</th><th>Joined</th><th>Plan</th></tr></thead>
                                <tbody>
                                    <?php foreach($referred_users as $ru) :
                                        $u_plan = get_user_meta($ru->ID, '_saas_subscription_plan', true) ?: 'Free';
                                        ?>
                                        <tr><td><?php echo esc_html($ru->display_name); ?></td><td><?php echo date('M j, Y', strtotime($ru->user_registered)); ?></td><td><?php echo ucfirst($u_plan); ?></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color:var(--text-muted); font-size:0.9rem;">No referrals yet. Time to share your link!</p>
                        <?php endif; ?>
                    </div>


                    <div class="dashboard-card">
                        <h4>Request Payout</h4>
                        <p style="font-size:0.8rem; color:var(--text-muted);">Minimum $50.00</p>
                        <form id="saas-payout-request-form">
                            <div class="field"><label>Amount ($)</label><input type="number" name="amount" min="50" step="0.01" required></div>
                            <div class="field">
                                <label>Method</label>
                                <select name="method">
                                    <option value="paypal">PayPal</option>
                                    <option value="bank">Bank Transfer</option>
                                </select>
                            </div>
                            <div class="field"><label>Payment Email/Account</label><input type="text" name="email" required></div>
                            <button type="submit" class="btn-primary" style="width:100%; background:var(--secondary);">Submit Request</button>
                        </form>
                    </div>

                    <div class="dashboard-card">
                        <h4>Marketing Materials</h4>
                        <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:15px;">Use these elite assets to boost your referrals.</p>
                        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:15px;">
                            <?php foreach($marketing_materials as $mm) : ?>
                                <div style="padding:15px; background:var(--bg-main); border-radius:10px; border:1px solid var(--border);">
                                    <small style="font-weight:bold; display:block; margin-bottom:5px;"><?php echo esc_html($mm['name']); ?> (<?php echo esc_html($mm['size']); ?>)</small>
                                    <img src="<?php echo esc_url($mm['img']); ?>" style="width:100%; border-radius:5px; margin-bottom:10px;">
                                    <button class="button copy-html-btn" style="width:100%;">Copy HTML</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        $affiliate_kit = get_option('saas_affiliate_marketing_kit') ?: [];
                        if($affiliate_kit) : ?>
                            <div style="margin-top:30px; padding-top:20px; border-top:1px solid #eee;">
                                <h4>Pro Marketing Strategy Kit</h4>
                                <div style="display:grid; gap:15px;">
                                    <?php foreach($affiliate_kit as $kit) : ?>
                                        <div style="padding:20px; background:#fff; border-radius:12px; border:1px solid var(--border);">
                                            <h5 style="margin:0 0-10px; font-weight:800;"><?php echo esc_html($kit['title']); ?></h5>
                                            <div style="font-size:0.9rem; color:var(--text-muted);"><?php echo wp_kses_post($kit['content']); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="dashboard-card">
                        <h4>Elite Sales Scripts</h4>
                        <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:15px;">Copy and paste these high-converting scripts to your favorite platforms.</p>
                        <?php
                        $scripts = get_option('saas_sales_scripts') ?: [
                            ['title' => 'Sample Outreach', 'content' => 'Hey [Name], I noticed your bio...']
                        ];
                        ?>
                        <div style="display:grid; gap:15px;">
                            <?php
                            $ref_link = home_url('/?ref=' . wp_get_current_user()->user_login);
                            foreach($scripts as $script) :
                                $parsed_content = str_replace(['[Link]', '[My Link]'], $ref_link, $script['content']);
                            ?>
                                <div style="padding:20px; background:var(--bg-main); border-radius:12px; border:1px solid var(--border);">
                                    <h5 style="margin:0 0 10px; font-weight:800;"><?php echo esc_html($script['title']); ?></h5>
                                    <pre style="white-space: pre-wrap; font-size: 0.85rem; color: var(--text-dark); background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #ddd;"><?php echo esc_html($parsed_content); ?></pre>
                                    <button class="button" onclick="const p = this.previousElementSibling; const t = document.createElement('textarea'); t.value = p.innerText; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t); this.innerText='Copied! ✅'; setTimeout(() => this.innerText='Copy Script', 2000);">Copy Script</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <h4>Contact Affiliate Manager</h4>
                        <form id="saas-support-msg-form">
                            <input type="hidden" name="subject" value="Affiliate Inquiry">
                            <textarea name="message" rows="3" placeholder="Questions about payouts or materials?" required></textarea>
                            <button type="submit" class="button" style="margin-top:10px;">Send Message</button>
                        </form>
                    </div>
                </div>

                <div id="tab-billing" class="saas-tab-content">
                    <div class="dashboard-card">
                        <div style="background:var(--primary-soft); padding:30px; border-radius:20px; border:1px solid var(--primary); margin-bottom:40px; display:flex; gap:30px; align-items:center; flex-wrap:wrap;">
                            <div style="font-size:3rem;">👑</div>
                            <div style="flex:1; min-width:300px;">
                                <h3 style="margin:0; color:var(--primary);">Ready to Join the Elite 1%?</h3>
                                <p style="margin:10px 0 0; color:var(--text-dark); line-height:1.6;">As a consultant, your time is your most valuable asset. Stop wasting it managing fragmented links. Upgrade to <strong>Elite Pro</strong> to unlock advanced lead capture, whitelabeling, and smart routing.</p>
                            </div>
                            <div style="flex-shrink:0;">
                                <button class="btn-primary" onclick="window.scrollTo({top: document.getElementById('plans-anchor').offsetTop, behavior: 'smooth'})">See Pro Benefits ↓</button>
                            </div>
                        </div>

                        <h3 id="plans-anchor" style="text-align:center;">Choose Your Path to Growth</h3>

                        <div style="max-width:500px; margin:20px auto 40px; text-align:center;" class="dashboard-card">
                            <h4 style="margin-top:0;">Apply Elite License</h4>
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:15px;">Have a promotional code or license key? Activate it here to upgrade your account instantly.</p>
                            <form id="saas-license-activate-form">
                                <div class="field" style="display:flex; gap:10px;">
                                    <input type="text" name="license_key" placeholder="ELITE-XXXX-XXXX-XXXX" required style="flex:1;">
                                    <button type="submit" class="button">Activate Key</button>
                                </div>
                            </form>
                            <script>
                            jQuery('#saas-license-activate-form').on('submit', function(e) {
                                e.preventDefault();
                                var $btn = jQuery(this).find('button');
                                $btn.prop('disabled', true).text('Verifying...');
                                jQuery.post(saas_dashboard_data.ajax_url, jQuery(this).serialize() + '&action=saas_validate_license&security=' + saas_dashboard_data.nonce, function(res) {
                                    if(res.success) {
                                        alert(res.data);
                                        location.reload();
                                    } else {
                                        alert('Error: ' + res.data);
                                    }
                                    $btn.prop('disabled', false).text('Activate Key');
                                });
                            });
                            </script>
                        </div>

                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:30px; margin-top:30px;">
                            <div class="plan-card" style="background:#fff; padding:32px; border-radius:24px; border:1px solid #e2e8f0; position:relative; overflow:hidden;">
                                <h4 style="font-size:1.2rem; margin:0; color:#64748b;">Free Plan</h4>
                                <div style="font-size:2.5rem; font-weight:900; margin:16px 0;">$0<small style="font-size:1rem;">/forever</small></div>
                                <ul style="list-style:none; padding:0; margin-bottom: 24px; line-height:2.2; font-size: 0.9rem; text-align:left;">
                                    <li>✓ 1 Profile</li>
                                    <li>✓ Standard Blocks</li>
                                    <li>✓ Basic Analytics</li>
                                    <li>✗ Custom Domains</li>
                                    <li>✗ Pro Backgrounds</li>
                                    <li>✗ Tracking Pixels</li>
                                </ul>
                                <button class="button" style="width:100%; pointer-events:none; opacity:0.6;">Current Plan</button>
                            </div>

                            <div class="plan-card" style="background:var(--primary-soft); padding:32px; border-radius:24px; border:2px solid var(--primary); position:relative; overflow:hidden;">
                                <div style="position:absolute; top:20px; right:-35px; background:var(--primary); color:#fff; padding:5px 40px; transform:rotate(45deg); font-size:0.75rem; font-weight:bold;">POPULAR</div>
                                <h4 style="font-size:1.5rem; margin:0; color:var(--primary);">Elite Pro</h4>
                                <div style="font-size:2.5rem; font-weight:900; margin:16px 0;">$19<small style="font-size:1rem;">/mo</small></div>
                                <ul style="list-style:none; padding:0; margin-bottom: 24px; line-height:2.2; font-size: 0.9rem; text-align:left;">
                                    <li>✓ Unlimited Profiles</li>
                                    <li>✓ All Premium Blocks</li>
                                    <li>✓ Real-time Deep Analytics</li>
                                    <li>✓ Custom Domain Mapping</li>
                                    <li>✓ Remove All Branding</li>
                                </ul>
                            <?php if ($is_pro && get_user_meta($user_id, '_saas_subscription_plan', true) === 'pro') :
                                $expiry = get_user_meta($user_id, '_saas_subscription_expiry', true);
                                ?>
                                <div style="color:var(--secondary); font-weight:bold; margin-bottom:15px;">✓ Your elite subscription is active</div>
                                <button id="saas-cancel-sub" class="button" style="width:100%; color:var(--danger);">Cancel Subscription</button>
                            <?php elseif (!$is_pro) : ?>
                                <div class="payment-options" style="display:flex; flex-direction:column; gap:10px;">
                                    <?php
                                    $gateway_mode = $payments->get_active_gateway();
                                    if ($gateway_mode === 'stripe' || $gateway_mode === 'user_select') : ?>
                                        <button class="btn-primary saas-checkout-btn" data-gateway="stripe" data-plan="pro" style="width:100%;">Upgrade with Stripe</button>
                                    <?php endif; ?>
                                    <?php if ($gateway_mode === 'paypal' || $gateway_mode === 'user_select') : ?>
                                        <button class="btn-primary saas-checkout-btn" data-gateway="paypal" data-plan="pro" style="width:100%; background:#0070ba;">Upgrade with PayPal</button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="plan-card" style="background:#0f172a; padding:32px; border-radius:24px; border:2px solid #1e293b; position:relative; overflow:hidden; color:#fff;">
                                <div style="position:absolute; top:20px; right:-35px; background:var(--accent); color:#000; padding:5px 40px; transform:rotate(45deg); font-size:0.75rem; font-weight:bold;">MAX SCALE</div>
                                <h4 style="font-size:1.5rem; margin:0; color:var(--accent);">Agency Unlimited</h4>
                                <div style="font-size:2.5rem; font-weight:900; margin:16px 0;">$49<small style="font-size:1rem;">/mo</small></div>
                                <ul style="list-style:none; padding:0; margin-bottom: 24px; line-height:2.2; font-size: 0.9rem; text-align:left; color:rgba(255,255,255,0.7);">
                                    <li>✓ Everything in Pro</li>
                                    <li>✓ Unlimited Sub-accounts</li>
                                    <li>✓ API & Webhook Access</li>
                                    <li>✓ White-label Client Funnels</li>
                                    <li>✓ Dedicated Account Manager</li>
                                </ul>
                            <?php if ($is_pro && get_user_meta($user_id, '_saas_subscription_plan', true) === 'agency') : ?>
                                <div style="color:var(--secondary); font-weight:bold; margin-bottom:15px;">✓ Your agency subscription is active</div>
                                <button id="saas-cancel-sub" class="button" style="width:100%; color:var(--danger); background:transparent;">Cancel Subscription</button>
                            <?php elseif (!$is_pro || get_user_meta($user_id, '_saas_subscription_plan', true) === 'pro') : ?>
                                <div class="payment-options" style="display:flex; flex-direction:column; gap:10px;">
                                    <?php
                                    $gateway_mode = $payments->get_active_gateway();
                                    if ($gateway_mode === 'stripe' || $gateway_mode === 'user_select') : ?>
                                        <button class="btn-primary saas-checkout-btn" data-gateway="stripe" data-plan="agency" style="width:100%; background:var(--accent); color:#000;">Upgrade to Agency (Stripe)</button>
                                    <?php endif; ?>
                                    <?php if ($gateway_mode === 'paypal' || $gateway_mode === 'user_select') : ?>
                                        <button class="btn-primary saas-checkout-btn" data-gateway="paypal" data-plan="agency" style="width:100%; background:#0070ba;">Upgrade to Agency (PayPal)</button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="max-width:600px; margin:40px auto 0;" class="dashboard-card">
                        <div class="field">
                            <label>Have an affiliate coupon?</label>
                            <div style="display:flex; gap:10px;">
                                <input type="text" id="saas-checkout-coupon" placeholder="Enter coupon code" style="flex:1;">
                                <button type="button" class="button" id="saas-apply-checkout-coupon">Apply</button>
                            </div>
                            <div id="coupon-status" style="font-size:0.75rem; margin-top:5px; font-weight:bold;"></div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:20px;">
                            <button id="saas-demo-upgrade-btn" class="button" style="background:var(--accent-soft); border-color:var(--accent); color:var(--accent);">⚡ Instant Demo Upgrade</button>
                            <button id="saas-simulate-payment-btn" class="button" style="background:var(--secondary-soft); border-color:var(--secondary); color:var(--secondary);">💸 Simulate Stripe Success</button>
                        </div>

                        <?php if ($is_pro) :
                            $expiry = get_user_meta($user_id, '_saas_subscription_expiry', true);
                            $u_plan = get_user_meta($user_id, '_saas_subscription_plan', true);
                            ?>
                            <div style="margin-top:20px; padding-top:20px; border-top:1px solid #eee; font-size:0.85rem; color:var(--text-muted);">
                                <strong>Plan:</strong> <?php echo strtoupper($u_plan); ?><br>
                                <strong>Next Billing:</strong> <?php echo $expiry ? date('M j, Y', $expiry) : 'Never'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    </div>

                    <div class="dashboard-card">
                        <h4>Payment History</h4>
                        <?php
                        $orders = get_posts(['post_type' => 'saas_order', 'author' => $user_id, 'numberposts' => 10]);
                        if($orders) : ?>
                            <table class="saas-table">
                                <thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach($orders as $o) : ?>
                                        <tr>
                                            <td><?php echo get_the_date('', $o->ID); ?></td>
                                            <td>$<?php echo get_post_meta($o->ID, '_saas_order_amount', true); ?></td>
                                            <td><?php echo ucfirst(get_post_meta($o->ID, '_saas_order_status', true)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color:var(--text-muted);">No transactions found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="tab-templates" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Template Library</h3>
                        <p style="margin-bottom:20px; color:var(--text-muted);">Choose a high-converting template to jumpstart your profile. ⚠️ Warning: Applying a template will replace your current blocks.</p>

                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px;">
                            <?php
                            $all_tpls = get_option('saas_templates') ?: [];
                            $tpl_icons = [
                                'coach' => '🚀', 'business' => '🏢', 'luxury' => '⚜️', 'freelancer' => '🎨',
                                'realtor' => '🏡', 'politician' => '🏛️', 'tiktok' => '📱', 'consultant' => '🧠',
                                'author' => '📘', 'lawyer' => '⚖️', 'doctor' => '🩺', 'influencer' => '📸', 'servant' => '🏛️', 'artist' => '🎨',
                                'podcast' => '🎙️', 'course' => '🎓', 'shop' => '🛒', 'charity' => '❤️'
                            ];

                            $categories = [
                                'Business & Strategy' => ['coach', 'business', 'consultant', 'agency', 'lawyer', 'realtor', 'course'],
                                'Creative & Social'   => ['influencer', 'tiktok', 'artist', 'freelancer', 'author', 'podcast', 'shop'],
                                'Public & Professional' => ['servant', 'politician', 'doctor', 'speaker', 'luxury', 'charity']
                            ];

                            foreach ($categories as $cat_title => $tpl_ids) : ?>
                                <div style="grid-column: 1 / -1; margin-top: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                                    <h4 style="margin:0; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-size: 0.8rem;"><?php echo $cat_title; ?></h4>
                                </div>
                                <?php foreach($tpl_ids as $id) :
                                    if (!isset($all_tpls[$id])) continue;
                                    $icon = $tpl_icons[$id] ?? '✨';
                                ?>
                                    <div class="template-card" style="border:1px solid var(--border); padding:20px; border-radius:15px; text-align:center; transition:all 0.3s; background:#fff;">
                                        <div style="font-size:2.5rem; margin-bottom:10px;"><?php echo $icon; ?></div>
                                        <h4 style="margin:0 0 15px; font-size: 1rem;"><?php echo esc_html(ucfirst(str_replace('_', ' ', $id))); ?></h4>
                                        <button class="button apply-template-btn" data-template="<?php echo $id; ?>" style="width:100%; background:var(--primary); color:#fff; border:none;">Apply Template</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div id="tab-training" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Elite Training Academy 🎓</h3>
                        <p>I want you to succeed. That's why I've put together these short, high-impact tutorials to help you master your new digital salesman.</p>

                        <?php
                        $training_vids = get_option('saas_training_academy') ?: [
                            ['title' => 'The 60-Second Setup', 'desc' => 'Go from zero to a live funnel in under a minute.', 'video_id' => 'setup'],
                            ['title' => 'Lead Magnet Magic', 'desc' => 'Capture contact info and build your email list.', 'video_id' => 'leads']
                        ];
                        $kb_articles = get_option('saas_knowledge_base') ?: [
                            ['title' => 'How to connect my own domain?', 'url' => '#'],
                            ['title' => 'Setting up Stripe for product sales', 'url' => '#']
                        ];
                        ?>

                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-top:30px;">
                            <?php foreach($training_vids as $v) : ?>
                                <div style="background:var(--bg-main); padding:20px; border-radius:15px; border:1px solid var(--border);">
                                    <div style="height:150px; background:linear-gradient(45deg, #000, #333); border-radius:10px; margin-bottom:15px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:3rem; cursor:pointer;" onclick="alert('Tutorial [<?php echo esc_js($v['video_id']); ?>] loading...')">▶️</div>
                                    <h4><?php echo esc_html($v['title']); ?></h4>
                                    <p class="field-hint"><?php echo esc_html($v['desc']); ?></p>
                                    <button class="button" style="width:100%;">Watch Now</button>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr style="margin:40px 0;">
                        <h4>Knowledge Base</h4>
                        <ul style="list-style:none; padding:0;">
                            <?php foreach($kb_articles as $art) : ?>
                                <li style="padding:15px 0; border-bottom:1px solid #eee;"><a href="<?php echo esc_url($art['url']); ?>" style="text-decoration:none; color:var(--primary); font-weight:700;"><?php echo esc_html($art['title']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="tab-account" class="saas-tab-content">
                    <div class="dashboard-card">
                        <h3>Account Settings</h3>
                        <p class="field-hint">Manage your personal information and security.</p>

                        <form id="saas-account-form">
                            <div class="field">
                                <label>Display Name</label>
                                <input type="text" name="display_name" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                            </div>
                            <div class="field">
                                <label>Email Address</label>
                                <input type="email" name="user_email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                            </div>
                            <div class="field">
                                <label>New Password (Leave blank to keep current)</label>
                                <input type="password" name="new_password">
                            </div>
                            <button type="submit" class="btn-primary">Save Account Details</button>
                        </form>
                    </div>
                </div>

                <div id="tab-inbox" class="saas-tab-content">
                    <div class="dashboard-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                            <h3>System Messages</h3>
                            <button class="button" onclick="document.getElementById('saas-new-msg-modal').style.display='flex'">Compose</button>
                        </div>
                        <div id="saas-message-list">
                            <?php
                            $messages = get_posts([
                                'post_type' => 'saas_message',
                                'meta_key' => '_saas_msg_recipient',
                                'meta_value' => $user_id,
                                'numberposts' => 20
                            ]);
                            if($messages) :
                                foreach($messages as $m) :
                                    $status = get_post_meta($m->ID, '_saas_msg_status', true);
                                    ?>
                                    <div class="message-item <?php echo $status; ?>" style="padding:15px; border-bottom:1px solid #eee; <?php if($status=='unread') echo 'background:var(--primary-soft);'; ?>">
                                        <div style="display:flex; justify-content:space-between;">
                                            <strong><?php echo esc_html($m->post_title); ?></strong>
                                            <small><?php echo get_the_date('M j', $m->ID); ?></small>
                                        </div>
                                        <p style="margin:10px 0; font-size:0.9rem;"><?php echo wp_trim_words($m->post_content, 20); ?></p>
                                    <button class="button view-message" data-id="<?php echo $m->ID; ?>">Read Full Message</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color:var(--text-muted);">No messages yet. Check back later for system updates.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div> <!-- End Main Area -->

        </div>

        <!-- Modals -->
        <div id="saas-notif-modal" class="saas-modal">
            <div class="saas-modal-content" style="max-width:400px;">
                <span class="close-modal">&times;</span>
                <h3>Recent Activity</h3>
                <div id="notif-list" style="max-height:300px; overflow-y:auto;">
                    <div style="padding:12px; border-bottom:1px solid #eee;">🚀 Welcome to your new dashboard!</div>
                    <?php
                    $recent = get_posts(['post_type' => 'saas_lead', 'author' => $user_id, 'numberposts' => 5]);
                    foreach($recent as $r) : ?>
                        <div style="padding:12px; border-bottom:1px solid #eee; font-size:0.85rem;">
                            <strong>New Lead:</strong> <?php echo esc_html(get_post_meta($r->ID, '_saas_lead_name', true)); ?>
                            <br><small style="color:#888;"><?php echo get_the_date('', $r->ID); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="saas-wizard-modal" class="saas-modal" style="<?php echo $show_wizard ? 'display:flex;' : ''; ?>">
            <div class="saas-modal-content" style="max-width:600px;">
                <span class="close-modal">&times;</span>
                <div class="wizard-step active" data-step="1">
                    <h3>Welcome! Let's build your profile 🚀</h3>
                    <p>What is your primary niche?</p>
                    <select id="wizard-niche" class="field">
                        <option value="servant">🏛️ Public Servant / Official</option>
                        <option value="coach">🚀 Business Coach</option>
                        <option value="creator">🎬 Digital Creator</option>
                        <option value="realtor">🏡 Real Estate Pro</option>
                        <option value="business">🏢 Corporate Entity</option>
                        <option value="speaker">🎙️ Public Speaker</option>
                        <option value="author">✍️ Author / Writer</option>
                        <option value="consultant">🧠 Strategy Consultant</option>
                        <option value="lawyer">⚖️ Lawyer / Legal</option>
                        <option value="doctor">🩺 Doctor / Healthcare</option>
                        <option value="artist">🎨 Artist / Designer</option>
                        <option value="agency">🏢 Agency Owner</option>
                        <option value="freelancer">🎨 Creative Freelancer</option>
                        <option value="tiktok">📱 Influencer / TikTok</option>
                        <option value="luxury">⚜️ Luxury Advisory</option>
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
        <div id="saas-message-modal" class="saas-modal">
            <div class="saas-modal-content">
                <span class="close-modal">&times;</span>
                <h3 id="msg-modal-title">Message Details</h3>
                <div id="msg-modal-content" style="line-height:1.6; margin-bottom:20px;"></div>
                <hr>
                <h4>Reply</h4>
                <form id="saas-reply-msg-form">
                    <input type="hidden" name="to_user" id="msg-reply-to">
                    <textarea name="message" rows="3" required placeholder="Type your reply here..."></textarea>
                    <button type="submit" class="btn-primary" style="margin-top:10px;">Send Reply</button>
                </form>
            </div>
        </div>

        <div id="saas-new-msg-modal" class="saas-modal">
            <div class="saas-modal-content">
                <span class="close-modal">&times;</span>
                <h3>New Message to Admin</h3>
                <form id="saas-new-support-msg">
                    <div class="field"><label>Subject</label><input type="text" name="subject" required></div>
                    <div class="field"><label>Message</label><textarea name="message" rows="5" required></textarea></div>
                    <button type="submit" class="btn-primary" style="width:100%;">Send Message</button>
                </form>
            </div>
        </div>

        <div id="saas-lead-modal" class="saas-modal">
            <div class="saas-modal-content">
                <span class="close-modal">&times;</span>
                <h3>Lead Details</h3>
                <div id="lead-details-content" style="line-height:1.8;"></div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }
}
new Saas_Dashboard();
