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
        $all_user_profiles = get_posts([
            'post_type'   => 'saas_profile',
            'post_author' => $user_id,
            'numberposts' => -1,
        ]);

        $active_profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;

        if ( empty( $all_user_profiles ) ) {
            // Auto-create profile if missing
            $profile_id = wp_insert_post([
                'post_type'   => 'saas_profile',
                'post_title'  => wp_get_current_user()->display_name,
                'post_status' => 'publish',
                'post_author' => $user_id,
            ]);
            $profile_obj = get_post($profile_id);
            $active_profile_id = $profile_id;
        } else {
            // Select active profile
            $profile_obj = null;
            if ($active_profile_id) {
                foreach($all_user_profiles as $p) {
                    if ($p->ID === $active_profile_id) {
                        $profile_obj = $p;
                        break;
                    }
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
                <div class="onboarding-flex">
                    <div>
                        <h4>🚀 Get Started Checklist</h4>
                        <div class="checklist-items">
                            <span class="check-item <?php echo $meta['bio'] ? 'completed' : ''; ?>"><?php echo $meta['bio'] ? '✓' : '○'; ?> Bio</span>
                            <span class="check-item <?php echo count($links) > 0 ? 'completed' : ''; ?>"><?php echo count($links) > 0 ? '✓' : '○'; ?> Blocks</span>
                            <span class="check-item <?php echo $is_pro ? 'completed' : ''; ?>"><?php echo $is_pro ? '✓' : '○'; ?> Pro Upgrade</span>
                        </div>
                    </div>
                    <button id="saas-start-wizard" class="wizard-trigger-btn">Launch Setup Wizard</button>
                </div>
            </div>

            <div class="saas-dashboard-header">
                <div class="header-left">
                    <div class="profile-switcher-wrapper">
                        <h2 class="profile-title"><?php echo esc_html($profile_obj->post_title); ?> <span class="chevron">▾</span></h2>
                        <div class="profile-dropdown">
                            <?php foreach($all_user_profiles as $up) : ?>
                                <a href="?profile_id=<?php echo $up->ID; ?>" class="dropdown-item <?php echo ($up->ID == $active_profile_id) ? 'active' : ''; ?>">
                                    <?php echo esc_html($up->post_title); ?>
                                </a>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <button id="saas-add-profile-trigger" class="add-profile-btn">+ New Profile</button>
                        </div>
                    </div>
                    <div class="saas-notification-bell" id="saas-notif-trigger">
                        🔔<span id="notif-count">0</span>
                    </div>
                </div>
                <div class="saas-share-bar">
                    <input type="text" id="saas-my-link" value="<?php echo home_url('/' . $profile_obj->post_name); ?>" readonly>
                    <button id="saas-copy-btn">Copy Link</button>
                </div>
            </div>
            <nav class="saas-tabs">
                <button class="active" data-tab="links">Links</button>
                <button data-tab="profile">Profile</button>
                <button data-tab="branding">Branding</button>
                <button data-tab="share">Share & QR</button>
                <button data-tab="seo">SEO & Icons</button>
                <button data-tab="automation">Lead Setup</button>
                <button data-tab="leads">Leads</button>
                <button data-tab="analytics">Analytics</button>
                <button data-tab="integrations">Integrations</button>
                <button data-tab="billing">Billing</button>
            </nav>

            <!-- Links Tab -->
            <div id="tab-links" class="saas-tab-content active">
                <h3 class="tab-title">Manage Blocks</h3>

                <!-- Visual Block Picker -->
                <div class="saas-block-picker">
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
                    <div class="picker-item pro-locked" data-type="product"><span>🛒</span> Shop 🔒</div>
                    <div class="picker-item" data-type="social_feed"><span>📱</span> Feed</div>
                    <div class="picker-item" data-type="lead_form"><span>🎯</span> Form</div>
                </div>

                <form id="saas-add-link-form">
                    <input type="hidden" name="block_type" id="saas-block-type-hidden" value="button">
                    <div class="field-row" style="display:flex; gap:10px; grid-column: 1/-1;">
                        <select name="block_style" id="saas-block-style" style="flex:1;">
                            <option value="regular">Regular Style</option>
                            <option value="featured">Featured (Pulse)</option>
                            <option value="rainbow">Rainbow Glow</option>
                            <option value="outline">Outline Only</option>
                            <option value="glow">Glow Effect</option>
                        </select>
                        <select name="block_animation" id="saas-block-animation" style="flex:1;">
                            <option value="fadeinup">Fade In Up</option>
                            <option value="bouncein">Bounce In</option>
                            <option value="none">No Animation</option>
                        </select>
                    </div>
                    <div class="field">
                        <input type="text" name="title" placeholder="Block Title (e.g. Book a Consultation)" required>
                        <small class="helper-note"><strong>Pro Tip:</strong> Use "Action Verbs" like <i>Grab, Join,</i> or <i>Book</i> to increase clicks by 25%.</small>
                    </div>
                    <div class="field">
                        <input type="url" name="url" placeholder="URL (e.g. https://calendly.com/yourname)" required>
                        <small class="helper-note"><strong>Pro Tip:</strong> Double check your link works before saving!</small>
                    </div>
                    <div class="field" style="grid-column: 1/-1;">
                        <textarea name="extra" placeholder="Extra content (e.g. FAQ Answer, Price, or Testimonial text)"></textarea>
                        <small class="helper-note">Use this for secondary text, pricing details, or FAQ answers.</small>
                    </div>
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
                            data-custom-text="<?php echo esc_attr(get_post_meta($link->ID, '_saas_custom_text', true)); ?>"
                            data-url-mobile="<?php echo esc_attr(get_post_meta($link->ID, '_saas_url_mobile', true)); ?>"
                            data-url-geo="<?php echo esc_attr(get_post_meta($link->ID, '_saas_url_geo', true)); ?>"
                            data-geo-country="<?php echo esc_attr(get_post_meta($link->ID, '_saas_url_geo_country', true)); ?>"
                            data-ab-title="<?php echo esc_attr(get_post_meta($link->ID, '_saas_ab_title_b', true)); ?>"
                            data-ab-url="<?php echo esc_attr(get_post_meta($link->ID, '_saas_ab_url_b', true)); ?>"
                            data-password="<?php echo esc_attr(get_post_meta($link->ID, '_saas_link_password', true)); ?>"
                            data-image-id="<?php echo esc_attr(get_post_meta($link->ID, '_saas_link_image_id', true)); ?>"
                            data-image-url="<?php echo esc_url(wp_get_attachment_thumb_url(get_post_meta($link->ID, '_saas_link_image_id', true))); ?>">
                            <span class="handle">⠿</span>
                            <?php
                            $thumb_id = get_post_meta($link->ID, '_saas_link_image_id', true);
                            if($thumb_id): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_thumb_url($thumb_id)); ?>" class="link-thumb-small">
                            <?php else: ?>
                                <div class="link-thumb-small" style="display:flex; align-items:center; justify-content:center; background:#f8f9fa; font-size:1.2rem;">🔗</div>
                            <?php endif; ?>
                            <div class="link-info">
                                <strong class="link-title"><?php echo esc_html( $link->post_title ); ?></strong>
                                <span class="link-url"><?php echo esc_url( get_post_meta( $link->ID, '_saas_link_url', true ) ); ?></span>
                            </div>
                            <span class="click-counter" title="Total Clicks">
                                📊 <?php
                                $ca = isset($link_stats[$link->ID]) ? (int)$link_stats[$link->ID]->clicks : 0;
                                $cb = isset($link_stats[$link->ID]) ? (int)$link_stats[$link->ID]->clicks_b : 0;
                                echo $ca + $cb;
                                if ($cb > 0) echo " <small>(A:$ca B:$cb)</small>";
                                ?>
                            </span>
                            <div class="block-actions">
                                <button class="edit-link">Edit</button>
                                <button class="delete-link">Delete</button>
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
                        <input type="text" name="headline" value="<?php echo esc_attr( $meta['headline'] ); ?>" placeholder="e.g. Helping 7-figure founders scale impact 🚀">
                        <small class="helper-note"><strong>Best Practice:</strong> Use an "Outcome-Based" headline. Instead of "I am a Coach", use "Helping [Niche] achieve [Result]". Max 60 chars recommended.</small>
                    </div>
                    <div class="field">
                        <label>Bio</label>
                        <textarea name="bio" placeholder="e.g. Ex-Google Exec turned Strategic Coach. I work with CEOs to automate acquisition and double profit margins."><?php echo esc_textarea( $meta['bio'] ); ?></textarea>
                        <small class="helper-note"><strong>Best Practice:</strong> Establish authority in the first sentence, then provide a clear Call to Action. Keep it under 160 characters for best mobile visibility.</small>
                    </div>
                    <div class="saas-conversion-card" style="background:#fff9eb; padding:20px; border-radius:12px; border:1px solid #ffeaa7; margin-bottom:24px;">
                        <h4 style="margin-top:0; color:#d6a317;">💡 Conversion Tip</h4>
                        <p style="font-size:0.85rem; margin-bottom:0;">Profiles with a clear <strong>"I help [who] with [what]"</strong> structure see 45% higher lead capture rates. Avoid technical jargon and focus on the benefit to your visitor.</p>
                    </div>
                    <div class="field">
                        <label>Phone Number (vCard)</label>
                        <input type="text" name="phone" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_phone', true)); ?>" placeholder="e.g. +1 (555) 000-1234">
                        <small class="helper-note"><strong>Best Practice:</strong> Use international format (+1...) to ensure "Save Contact" works globally. This enables the 1-tap networking feature.</small>
                    </div>
                    <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                        <label><input type="checkbox" name="verified_badge" value="1" <?php checked(get_post_meta($profile_id, '_saas_verified_badge', true), 1); ?> <?php if(!$is_pro) echo 'disabled'; ?>> Show Verified Badge ✅ <?php if(!$is_pro) echo '🔒'; ?></label>
                        <small>Adds a blue verification checkmark next to your name to build elite authority.</small>
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
                        <label>Lead Magnet (Automatic Download)</label>
                        <div id="lead-magnet-preview" style="margin-bottom:10px;">
                            <?php
                            $lm_url = get_post_meta($profile_id, '_saas_lead_magnet_url', true);
                            if ($lm_url) : ?><span>📄 <?php echo basename($lm_url); ?></span><?php endif; ?>
                        </div>
                        <input type="hidden" name="lead_magnet_url" id="saas-lead-magnet-url" value="<?php echo esc_url($lm_url); ?>">
                        <button type="button" class="button" id="saas-lead-magnet-upload">Select File</button>
                    </div>
                    <div class="field">
                        <label>Redirect URL after Submit</label>
                        <input type="url" name="lead_redirect" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_redirect', true)); ?>" placeholder="https://yoursite.com/thank-you">
                    </div>
                    <div class="field">
                        <label>Webhook URL (Zapier/Make)</label>
                        <div style="display:flex; gap:10px;">
                            <input type="url" name="lead_webhook" id="lead-webhook-url" value="<?php echo esc_url(get_post_meta($profile_id, '_saas_lead_webhook', true)); ?>" placeholder="https://hooks.zapier.com/..." style="flex:1;">
                            <button type="button" id="saas-test-webhook" class="button">Test Webhook</button>
                        </div>
                        <small class="helper-note">Automatically send your leads to other apps like Google Sheets or Slack. Paste your automation webhook here.</small>
                    </div>
                    <div class="field">
                        <label>Custom Success Message</label>
                        <input type="text" name="lead_success_msg" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_lead_success_msg', true)); ?>" placeholder="Thank you! We will contact you soon.">
                    </div>
                    <div class="field">
                        <label>Lead Capture Form Builder</label>
                        <table class="saas-mini-table">
                            <thead><tr><th>Field</th><th>Show</th><th>Label</th><th>Req</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td>Phone</td>
                                    <td><input type="checkbox" name="form_field_phone" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_phone', true), 1); ?>></td>
                                    <td><input type="text" name="form_label_phone" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_form_label_phone', true) ?: 'Phone Number'); ?>" placeholder="Phone Label"></td>
                                    <td><input type="checkbox" name="form_req_phone" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_req_phone', true), 1); ?>></td>
                                </tr>
                                <tr>
                                    <td>Message</td>
                                    <td><input type="checkbox" name="form_field_msg" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_msg', true), 1); ?>></td>
                                    <td><input type="text" name="form_label_msg" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_form_label_msg', true) ?: 'Your Message'); ?>" placeholder="Message Label"></td>
                                    <td><input type="checkbox" name="form_req_msg" value="1" <?php checked(get_post_meta($profile_id, '_saas_form_req_msg', true), 1); ?>></td>
                                </tr>
                            </tbody>
                        </table>
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
                        <?php
                        $qr_color = get_post_meta($profile_id, '_saas_qr_color', true) ?: '000000';
                        $qr_url = saas_get_profile_qr_url($profile_obj->post_name, $qr_color);
                        ?>
                        <img id="saas-qr-preview" src="<?php echo $qr_url; ?>" alt="QR Code" style="background:#fff; padding:10px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                        <p><small>Scan to view profile</small></p>

                        <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>" style="margin-top:15px;">
                            <label>QR Branding Color</label>
                            <input type="color" id="qr-color-picker" value="#<?php echo $qr_color; ?>" <?php if(!$is_pro) echo 'disabled'; ?>>
                        </div>

                        <a id="saas-qr-download" href="<?php echo $qr_url; ?>" download="qr-code.png" class="button">Download PNG</a>
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

                <div class="saas-nfc-guide" style="margin-top:40px; background:#fff; padding:30px; border-radius:16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #eee;">
                    <div style="display:flex; gap:30px; align-items:center;">
                        <div style="font-size:3rem;">📳</div>
                        <div>
                            <h4>Elite Networking: NFC Card Setup</h4>
                            <p style="color:#666; font-size:0.9rem;">Turn any NFC-enabled business card into a digital lead machine. Use the settings below to encode your card.</p>
                            <div style="background:#f8f9fa; padding:15px; border-radius:8px; font-family:monospace; margin:10px 0; border:1px dashed #ccc;">
                                <?php echo home_url('/' . $profile_obj->post_name . '?src=nfc'); ?>
                            </div>
                            <small>Step 1: Download an 'NFC Tools' app. Step 2: Write the URL above to your card. Step 3: Tap any phone to share your identity.</small>
                        </div>
                    </div>
                </div>

                <div class="saas-pwa-guide" style="margin-top:20px; background:#fff; padding:30px; border-radius:16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #eee;">
                    <div style="display:flex; gap:30px; align-items:center;">
                        <div style="font-size:3rem;">📲</div>
                        <div>
                            <h4>Install as App (Save to Home Screen)</h4>
                            <p style="color:#666; font-size:0.9rem;">Ensure your clients can access you in one tap. Instruct them to follow these steps:</p>
                            <p style="font-size:0.8rem; margin:0;"><strong>iPhone:</strong> Tap 'Share' icon (square with arrow) -> 'Add to Home Screen'</p>
                            <p style="font-size:0.8rem; margin:0;"><strong>Android:</strong> Tap three-dot menu -> 'Add to Home Screen'</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO Tab -->
            <div id="tab-seo" class="saas-tab-content">
                <h3>SEO & Profile Discovery</h3>
                <form id="saas-seo-form">
                    <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                    <div class="field">
                        <label>Meta Title Tag</label>
                        <input type="text" name="meta_title" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_seo_title', true)); ?>" placeholder="e.g. Alex Coach | High-Performance Coaching">
                        <small class="helper-note">This title appears in browser tabs and Google search results. Keep it relevant to your name and service.</small>
                    </div>
                    <div class="field">
                        <label>Meta Description</label>
                        <textarea name="meta_desc" id="seo-meta-desc" rows="3" placeholder="Page description for Google"><?php echo esc_textarea(get_post_meta($profile_id, '_saas_seo_desc', true)); ?></textarea>
                        <small class="helper-note">A brief summary of your profile (150-160 chars). This is what people see under your link in Google.</small>
                    </div>

                    <div class="seo-preview-wrapper" style="background:#fff; border:1px solid #eee; padding:20px; border-radius:12px; margin-bottom:24px;">
                        <h4 style="margin-top:0; font-size:0.8rem; color:#888; text-transform:uppercase;">Google Search Preview</h4>
                        <div class="google-mockup" style="font-family: arial, sans-serif; max-width: 600px;">
                            <div class="mock-url" style="color: #202124; font-size: 14px; margin-bottom: 4px; display:flex; align-items:center; gap:8px;">
                                <div style="background:#f1f3f4; border-radius:50%; width:24px; height:24px; display:flex; align-items:center; justify-content:center; font-size:10px;">🌎</div>
                                <span><?php echo home_url('/' . $profile_obj->post_name); ?></span>
                            </div>
                            <div class="mock-title" id="seo-mock-title" style="color: #1a0dab; font-size: 20px; line-height: 1.3; margin-bottom: 3px; cursor: pointer; text-decoration: none;">
                                <?php echo esc_html(get_post_meta($profile_id, '_saas_seo_title', true) ?: $profile_obj->post_title . ' | Digital Business Card'); ?>
                            </div>
                            <div class="mock-desc" id="seo-mock-desc" style="color: #4d5156; font-size: 14px; line-height: 1.58;">
                                <?php echo esc_html(get_post_meta($profile_id, '_saas_seo_desc', true) ?: 'Check out my professional profile and links. Contact me directly for inquiries.'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label>Custom Favicon</label>
                        <div id="favicon-preview" style="margin-bottom:10px;">
                            <?php
                            $favicon = get_post_meta($profile_id, '_saas_favicon', true);
                            if ($favicon) : ?><img src="<?php echo esc_url($favicon); ?>" style="width:32px; height:32px;"><?php endif; ?>
                        </div>
                        <input type="hidden" name="favicon" id="saas-favicon-url" value="<?php echo esc_url($favicon); ?>">
                        <button type="button" class="button" id="saas-favicon-upload">Upload Icon</button>
                    </div>
                    <button type="submit">Save SEO Settings</button>
                </form>
            </div>

            <!-- Tracking & Analytics Tab (Pro Only) -->
            <div id="tab-tracking" class="saas-tab-content">
                <h3>Custom Tracking & Scripts</h3>
                <div style="position:relative;">
                <form id="saas-tracking-form" class="<?php echo $is_pro ? '' : 'pro-gated'; ?>">
                    <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                    <div class="field">
                        <label>Custom Header Scripts (e.g. Google Analytics / Meta Pixel)</label>
                        <textarea name="header_scripts" rows="6" placeholder="<script>...</script>" <?php if(!$is_pro) echo 'disabled'; ?>><?php echo esc_textarea(get_post_meta($profile_id, '_saas_header_scripts', true)); ?></textarea>
                    </div>
                    <div class="field">
                        <label>Custom Footer Scripts</label>
                        <textarea name="footer_scripts" rows="6" placeholder="<script>...</script>" <?php if(!$is_pro) echo 'disabled'; ?>><?php echo esc_textarea(get_post_meta($profile_id, '_saas_footer_scripts', true)); ?></textarea>
                    </div>
                    <button type="submit">Save Scripts</button>
                </form>
                <?php if(!$is_pro) : ?><div class="pro-overlay"><button type="button" onclick="document.querySelector('[data-tab=billing]').click()">Upgrade to Pro to add custom tracking</button></div><?php endif; ?>
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
                        <small class="helper-note">Choose a color that matches your personal brand or logo.</small>
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
                        <label>Profile Theme (Mode)</label>
                        <select name="profile_theme">
                            <option value="light" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'light'); ?>>Light Mode</option>
                            <option value="dark" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'dark'); ?>>Dark Mode</option>
                            <option value="vibrant" <?php selected(get_post_meta($profile_id, '_saas_profile_theme', true), 'vibrant'); ?>>Vibrant (Glass)</option>
                        </select>
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
                        <label>Container Shadow</label>
                        <select name="container_shadow">
                            <option value="none" <?php selected(get_post_meta($profile_id, '_saas_container_shadow', true), 'none'); ?>>None</option>
                            <option value="soft" <?php selected(get_post_meta($profile_id, '_saas_container_shadow', true), 'soft'); ?>>Soft Glow</option>
                            <option value="hard" <?php selected(get_post_meta($profile_id, '_saas_container_shadow', true), 'hard'); ?>>Retro Hard Shadow</option>
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
                        <label>One-Click Style Presets</label>
                        <div class="saas-style-presets" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; margin-bottom:20px;">
                            <button type="button" class="preset-btn" data-preset="midnight" style="background:#1a1a1a; color:#fff; border:none; padding:10px; border-radius:8px;">Midnight</button>
                            <button type="button" class="preset-btn" data-preset="glass" style="background:#eee; color:#333; border:1px solid #ddd; padding:10px; border-radius:8px;">Glassy</button>
                            <button type="button" class="preset-btn" data-preset="vibrant" style="background:linear-gradient(45deg, #f093fb, #f5576c); color:#fff; border:none; padding:10px; border-radius:8px;">Vibrant</button>
                            <button type="button" class="preset-btn" data-preset="minimal" style="background:#fff; color:#333; border:1px solid #ddd; padding:10px; border-radius:8px;">Minimal</button>
                            <button type="button" class="preset-btn" data-preset="luxury" style="background:#1a1a1a; color:#d4af37; border:1px solid #d4af37; padding:10px; border-radius:8px;">Luxury</button>
                        </div>
                    </div>
                    <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                        <label><input type="checkbox" name="social_proof" value="1" <?php checked(get_post_meta($profile_id, '_saas_social_proof', true), 1); ?> <?php if(!$is_pro) echo 'disabled'; ?>> Enable Social Proof Pulse <?php if(!$is_pro) echo '🔒'; ?></label>
                        <small>Shows a live view count bubble on your profile to build trust.</small>
                    </div>
                    <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                        <label><input type="checkbox" name="hide_branding" value="1" <?php checked(get_post_meta($profile_id, '_saas_hide_branding', true), 1); ?> <?php if(!$is_pro) echo 'disabled'; ?>> Hide "Powered by" Branding <?php if(!$is_pro) echo '🔒'; ?></label>
                        <small>Whitelabel your profile by removing our platform links.</small>
                    </div>
                    <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                        <label>Custom Footer Text (Pro Only)</label>
                        <input type="text" name="footer_text" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_footer_text', true)); ?>" placeholder="e.g. © 2024 Your Agency Name" <?php if(!$is_pro) echo 'disabled'; ?>>
                        <small>Replace our branding with your own custom footer text.</small>
                    </div>
                    <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                        <label>Custom Domain <?php if(!$is_pro) echo '🔒'; ?></label>
                        <input type="text" name="custom_domain" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_custom_domain', true)); ?>" placeholder="e.g. bio.yourname.com" <?php if(!$is_pro) echo 'disabled'; ?>>
                        <small>Point your CNAME record to our server IP to use your own domain.</small>
                    </div>
                    <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                        <label>Profile Access Password <?php if(!$is_pro) echo '🔒'; ?></label>
                        <input type="text" name="profile_password" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_profile_password', true)); ?>" placeholder="Leave empty for public access" <?php if(!$is_pro) echo 'disabled'; ?>>
                        <small>Lock your entire profile behind a password. Perfect for private portfolios or client-only assets.</small>
                    </div>
                    <div class="field <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                        <label>Custom CSS (Pro Only) <?php if(!$is_pro) echo '🔒'; ?></label>
                        <textarea name="custom_css" rows="5" placeholder=".saas-link-btn { border: 2px solid gold; }" <?php if(!$is_pro) echo 'disabled'; ?>><?php echo esc_textarea(get_post_meta($profile_id, '_saas_custom_css', true)); ?></textarea>
                        <small>Add custom styles to your profile page.</small>
                    </div>
                    <div class="field">
                        <label>Apply Page Template</label>
                        <select id="saas-apply-template">
                            <option value="">Select Template...</option>
                            <option value="coach">Coach Funnel</option>
                            <option value="freelancer">Freelancer Portfolio</option>
                            <option value="realtor">Real Estate / Local Biz</option>
                            <option value="business">Business Page</option>
                            <option value="politician">Politician / Public Service</option>
                            <option value="elite_card">Elite Digital Card</option>
                            <option value="tiktok">TikTok / Affiliate Pro</option>
                            <option value="consultant">Expert Consultant</option>
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

                <div class="crm-filters" style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; gap:10px;">
                        <select id="crm-filter-status">
                            <option value="all">All Statuses</option>
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="converted">Converted</option>
                        </select>
                        <input type="text" id="crm-search-leads" placeholder="Search leads...">
                    </div>
                    <button id="saas-bulk-delete-leads" class="button" style="background:#fff0f0; color:#ff7675; border:1px solid #ffeaea; display:none;">Delete Selected</button>
                </div>

                <?php
                $leads = get_posts([
                    'post_type' => 'saas_lead',
                    'post_author' => $user_id,
                    'numberposts' => 100
                ]);
                if ($leads) : ?>
                    <table class="saas-table" id="leads-table">
                        <thead><tr><th><input type="checkbox" id="leads-select-all"></th><th>Name</th><th>Email</th><th>Status</th><th>Source</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($leads as $lead) :
                            $status = get_post_meta($lead->ID, '_saas_lead_status', true) ?: 'New';
                            $source_id = get_post_meta($lead->ID, '_saas_lead_source_id', true);
                            $source_name = $source_id ? get_the_title($source_id) : 'Direct';
                            ?>
                            <tr class="lead-row-<?php echo esc_attr(strtolower($status)); ?>" data-id="<?php echo $lead->ID; ?>">
                                <td><input type="checkbox" class="lead-checkbox" value="<?php echo $lead->ID; ?>"></td>
                                <td data-label="Name"><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_name', true)); ?></td>
                                <td data-label="Email"><?php echo esc_html(get_post_meta($lead->ID, '_saas_lead_email', true)); ?></td>
                                <td data-label="Status">
                                    <span class="status-badge <?php echo esc_attr(strtolower($status)); ?>"><?php echo esc_html($status); ?></span>
                                </td>
                                <td data-label="Source"><small><?php echo esc_html($source_name); ?></small></td>
                                <td data-label="Date"><?php echo get_the_date('', $lead->ID); ?></td>
                                <td data-label="Actions">
                                    <div style="display:flex; gap:10px;">
                                        <button class="view-lead-btn" data-id="<?php echo $lead->ID; ?>">View</button>
                                        <button class="view-on-profile-btn button" style="padding:4px 8px; font-size:0.7rem; background:#f0f0f0; color:#333;">🔗 Profile</button>
                                        <button class="delete-lead-btn" data-id="<?php echo $lead->ID; ?>" style="color:#ff7675; border:none; background:none; cursor:pointer; font-size:0.7rem;">Delete</button>
                                    </div>
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
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3>Profile Insights</h3>
                    <a href="<?php echo admin_url('admin-ajax.php?action=saas_export_analytics&security='.wp_create_nonce('saas_export_nonce')); ?>" class="button button-secondary">Download CSV</a>
                </div>
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
                        <label>Leads Captured</label>
                        <div class="value"><?php echo number_format($stats['leads']); ?></div>
                    </div>
                    <div class="stat-card">
                        <label>Conversion Rate</label>
                        <div class="value"><?php echo ($stats['views'] > 0) ? round(($stats['leads'] / $stats['views']) * 100, 1) : 0; ?>%</div>
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

                <div style="margin-top:50px; border-top: 2px solid #eee; padding-top:40px;">
                    <h4>💰 Revenue & Orders</h4>
                    <?php
                    $orders = get_posts([
                        'post_type' => 'saas_order',
                        'post_author' => $user_id,
                        'numberposts' => 10
                    ]);
                    $total_rev = 0;
                    foreach($orders as $o) $total_rev += floatval(get_post_meta($o->ID, '_saas_order_amount', true));
                    ?>
                    <div class="stat-card" style="display:inline-block; margin-bottom:20px; text-align:left;">
                        <label>Total Revenue Generated</label>
                        <div class="value">$<?php echo number_format($total_rev, 2); ?></div>
                    </div>

                    <?php if ($orders) : ?>
                        <table class="saas-table">
                            <thead><tr><th>Item</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($orders as $order) : ?>
                                <tr>
                                    <td><?php echo esc_html($order->post_title); ?></td>
                                    <td>$<?php echo number_format(get_post_meta($order->ID, '_saas_order_amount', true), 2); ?></td>
                                    <td><span class="status-badge new"><?php echo esc_html(get_post_meta($order->ID, '_saas_order_status', true)); ?></span></td>
                                    <td><?php echo get_the_date('', $order->ID); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p>No transactions yet. Start selling products via your profile!</p>
                    <?php endif; ?>
                </div>
            </div>
            <div id="tab-integrations" class="saas-tab-content">
                <h3>Integrations Hub</h3>
                <div style="position:relative;">
                    <form id="saas-integrations-form" class="<?php echo $is_pro ? '' : 'pro-gated'; ?>">
                        <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">

                        <div class="integration-card" style="background:#f8f9fa; padding:30px; border-radius:24px; margin-bottom:20px; border:1px solid #eee;">
                            <div style="display:flex; align-items:center; gap:20px; margin-bottom:20px;">
                                <div style="font-size:2.5rem;">🐵</div>
                                <div>
                                    <h4 style="margin:0;">Mailchimp</h4>
                                    <p style="margin:0; font-size:0.85rem; color:#666;">Sync new leads automatically to your audience.</p>
                                </div>
                            </div>
                            <div class="field">
                                <label>Mailchimp API Key</label>
                                <input type="password" name="mailchimp_api" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_mailchimp_api', true)); ?>" placeholder="xxxx-us1">
                            </div>
                            <div class="field">
                                <label>Audience / List ID</label>
                                <input type="text" name="mailchimp_list" id="mailchimp-list-id" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_mailchimp_list', true)); ?>" placeholder="e.g. a1b2c3d4e5">
                            </div>
                            <button type="button" class="button saas-check-integration" data-platform="mailchimp">Check Mailchimp Connection</button>
                        </div>

                        <div class="integration-card" style="background:#f8f9fa; padding:30px; border-radius:24px; border:1px solid #eee;">
                            <div style="display:flex; align-items:center; gap:20px; margin-bottom:20px;">
                                <div style="font-size:2.5rem;">🧡</div>
                                <div>
                                    <h4 style="margin:0;">HubSpot</h4>
                                    <p style="margin:0; font-size:0.85rem; color:#666;">Send contact info directly to HubSpot CRM.</p>
                                </div>
                            </div>
                            <div class="field">
                                <label>Private App Access Token</label>
                                <input type="password" name="hubspot_token" id="hubspot-token" value="<?php echo esc_attr(get_post_meta($profile_id, '_saas_hubspot_token', true)); ?>" placeholder="pat-na1-xxxx">
                            </div>
                            <button type="button" class="button saas-check-integration" data-platform="hubspot">Check HubSpot Connection</button>
                        </div>

                        <button type="submit" style="margin-top:20px;">Save Integrations</button>
                    </form>
                    <?php if(!$is_pro) : ?><div class="pro-overlay"><button type="button" onclick="document.querySelector('[data-tab=billing]').click()">Upgrade to Pro to unlock Integrations</button></div><?php endif; ?>
                </div>
            </div>

            <div id="tab-billing" class="saas-tab-content">
                <div class="billing-header" style="text-align:center; margin-bottom:40px;">
                    <h3 style="font-size:2rem; margin-bottom:10px;">Upgrade Your Potential</h3>
                    <p style="color:var(--text-muted);">Join 10,000+ professionals using Pro features to scale.</p>
                </div>

                <div class="saas-plans-grid">
                    <div class="plan-card <?php echo !$is_pro ? 'active-plan' : ''; ?>">
                        <div class="plan-header">
                            <h4>Basic</h4>
                            <div class="price">$0<span>/mo</span></div>
                        </div>
                        <ul class="plan-features">
                            <li><span class="check">✓</span> 1 Profile</li>
                            <li><span class="check">✓</span> Unlimited Basic Links</li>
                            <li><span class="check">✓</span> Standard QR Code</li>
                            <li class="disabled">✕ Advanced Lead CRM</li>
                            <li class="disabled">✕ Whitelabel (No Branding)</li>
                            <li class="disabled">✕ Custom Tracking Pixels</li>
                        </ul>
                        <button disabled><?php echo !$is_pro ? 'Current Plan' : 'Free Tier'; ?></button>
                    </div>

                    <div class="plan-card featured <?php echo $is_pro ? 'active-plan' : ''; ?>">
                        <div class="popular-tag">MOST POPULAR</div>
                        <div class="plan-header">
                            <h4>Elite Pro</h4>
                            <div class="price">$19<span>/mo</span></div>
                        </div>
                        <ul class="plan-features">
                            <li><span class="check">✓</span> 10+ Profiles</li>
                            <li><span class="check">✓</span> <strong>All Premium Blocks</strong></li>
                            <li><span class="check">✓</span> Lead CRM & Automations</li>
                            <li><span class="check">✓</span> Webhook Integrations</li>
                            <li><span class="check">✓</span> Whitelabeling</li>
                            <li><span class="check">✓</span> Priority Support</li>
                        </ul>

                        <?php if ( $is_pro ) : ?>
                            <div class="active-status">
                                <p><strong>Plan Active:</strong> Elite Pro Subscription</p>
                                <button class="button button-secondary" onclick="alert('Redirecting to Billing Portal...')">Manage Subscription</button>
                            </div>
                        <?php else : ?>
                            <form class="checkout-form">
                                <input type="hidden" name="plan_id" value="pro">
                                <?php
                                $gateway_logic = $payments->get_active_gateway();
                                if ($gateway_logic === 'user_select') : ?>
                                    <select name="gateway" style="margin-bottom:15px; width:100%; padding:12px; border-radius:12px; border:1px solid #ddd; font-weight:600;">
                                        <option value="stripe">Pay with Card (Stripe)</option>
                                        <option value="paypal">Pay with PayPal</option>
                                    </select>
                                <?php elseif ($gateway_logic === 'stripe') : ?>
                                    <input type="hidden" name="gateway" value="stripe">
                                    <p style="font-size:0.8rem; color:#888; margin-bottom:15px; text-align:center;">Secure Payment via Stripe</p>
                                <?php elseif ($gateway_logic === 'paypal') : ?>
                                    <input type="hidden" name="gateway" value="paypal">
                                    <p style="font-size:0.8rem; color:#888; margin-bottom:15px; text-align:center;">Secure Payment via PayPal</p>
                                <?php endif; ?>
                                <button type="submit" class="cta-btn" <?php if($gateway_logic === 'none') echo 'disabled'; ?>>Unlock Everything Now</button>
                            </form>
                        <?php endif; ?>
                        <p style="font-size:0.7rem; color:rgba(255,255,255,0.6); text-align:center; margin-top:15px;">30-Day Money Back Guarantee</p>
                    </div>
                </div>

                <div class="billing-faq" style="margin-top:60px;">
                    <h4 style="text-align:center; margin-bottom:30px;">Common Questions</h4>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">
                        <div>
                            <strong>Can I cancel anytime?</strong>
                            <p style="font-size:0.85rem; color:var(--text-muted);">Yes, you can cancel your subscription from your dashboard at any time. No questions asked.</p>
                        </div>
                        <div>
                            <strong>Do you offer refunds?</strong>
                            <p style="font-size:0.85rem; color:var(--text-muted);">We offer a full 30-day money-back guarantee if you're not satisfied with the Pro features.</p>
                        </div>
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

        <!-- Setup Wizard Modal -->
        <div id="saas-wizard-modal" class="saas-modal">
            <div class="saas-modal-content wizard-content">
                <span class="close-modal">&times;</span>
                <div id="wizard-steps">
                    <!-- Step 1: Profile Photo -->
                    <div class="wizard-step active" data-step="1">
                        <h3>Step 1: Your Brand Identity</h3>
                        <p>A professional photo increases conversions by 40%.</p>
                        <div class="wizard-image-selector" style="text-align:center; margin:30px 0;">
                            <div id="wizard-photo-preview" style="width:120px; height:120px; border-radius:50%; background:#eee; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                <?php if ( has_post_thumbnail($profile_id) ) : echo get_the_post_thumbnail($profile_id, 'thumbnail'); else: ?><span>📷</span><?php endif; ?>
                            </div>
                            <button type="button" class="button" id="wizard-photo-btn">Select Profile Photo</button>
                        </div>
                        <div class="wizard-footer">
                            <button class="button next-step">Next: Bio & Headline</button>
                        </div>
                    </div>

                    <!-- Step 2: Bio & Headline -->
                    <div class="wizard-step" data-step="2">
                        <h3>Step 2: Tell your story</h3>
                        <p>Briefly explain what you do and how you help.</p>
                        <div class="field">
                            <label>Professional Headline</label>
                            <input type="text" id="wizard-headline" value="<?php echo esc_attr($meta['headline']); ?>" placeholder="e.g. Digital Marketing Consultant">
                        </div>
                        <div class="field">
                            <label>Short Bio</label>
                            <textarea id="wizard-bio" rows="3" placeholder="e.g. Helping businesses scale through high-performance ads."><?php echo esc_textarea($meta['bio']); ?></textarea>
                        </div>
                        <div class="wizard-footer">
                            <button class="button prev-step">Back</button>
                            <button class="button next-step">Next: Themes</button>
                        </div>
                    </div>

                    <!-- Step 3: Themes -->
                    <div class="wizard-step" data-step="3">
                        <h3>Step 3: Choose your vibe</h3>
                        <p>Select a primary color that matches your brand.</p>
                        <div class="field">
                            <label>Brand Primary Color</label>
                            <input type="color" id="wizard-color" value="<?php echo esc_attr($meta['theme_color']); ?>" style="width:100%; height:50px; border-radius:8px;">
                        </div>
                        <div class="wizard-footer">
                            <button class="button prev-step">Back</button>
                            <button class="button next-step">Final Step: Share</button>
                        </div>
                    </div>

                    <!-- Step 4: Finish -->
                    <div class="wizard-step" data-step="4">
                        <h3>You're all set! 🚀</h3>
                        <p>Your professional profile is ready to go. Share it on your socials to start capturing leads.</p>
                        <div class="wizard-share-preview" style="background:#f8f9fa; padding:20px; border-radius:12px; margin:20px 0; text-align:center;">
                            <img src="<?php echo saas_get_profile_qr_url($profile_obj->post_name); ?>" width="100">
                            <p><strong><?php echo home_url('/' . $profile_obj->post_name); ?></strong></p>
                        </div>
                        <div class="wizard-footer">
                            <button class="button prev-step">Back</button>
                            <button class="button button-primary" id="wizard-finish-btn">Finish & Save</button>
                        </div>
                    </div>
                </div>
                <div class="wizard-progress">
                    <div class="progress-bar-fill" style="width: 25%;"></div>
                </div>
            </div>
        </div>

        <!-- Notifications Modal -->
        <div id="saas-notif-modal" class="saas-modal">
            <div class="saas-modal-content" style="max-width:400px;">
                <span class="close-modal">&times;</span>
                <h3>Notifications</h3>
                <div id="saas-notif-list" style="max-height:400px; overflow-y:auto;">
                    <?php
                    $unread_leads = get_posts([
                        'post_type' => 'saas_lead',
                        'post_author' => $user_id,
                        'meta_query' => [
                            ['key' => '_saas_lead_status', 'value' => 'New']
                        ],
                        'numberposts' => 5
                    ]);
                    if ($unread_leads) :
                        foreach ($unread_leads as $ul) : ?>
                            <div class="notif-item" style="padding:15px; border-bottom:1px solid #eee;">
                                <strong>New Lead:</strong> <?php echo esc_html(get_post_meta($ul->ID, '_saas_lead_name', true)); ?>
                                <br><small><?php echo get_the_date('', $ul->ID); ?></small>
                            </div>
                        <?php endforeach;
                    else : ?>
                        <p>No new notifications.</p>
                    <?php endif; ?>
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
                        <small class="helper-note">Keep it snappy and clear.</small>
                    </div>
                    <div class="field-row" style="display:flex; gap:10px;">
                        <div class="field" style="flex:1;">
                            <label>Thumbnail / Icon</label>
                            <div id="edit-link-thumb-preview" style="width:50px; height:50px; background:#eee; margin-bottom:10px; border-radius:8px; overflow:hidden;"></div>
                            <input type="hidden" name="link_image_id" id="edit-link-image-id">
                            <button type="button" class="button" id="edit-link-image-btn">Upload</button>
                        </div>
                    </div>
                    <div class="field">
                        <label>URL / Embed</label>
                        <input type="url" name="url" id="edit-link-url" required>
                        <small class="helper-note">For videos, use the YouTube/Vimeo watch link.</small>
                    </div>

                    <div id="ab-testing-settings" class="field-row <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>" style="background:#f8f9fa; padding:15px; border-radius:12px; margin-top:10px;">
                        <h4>A/B Split Testing <?php if(!$is_pro) echo '🔒'; ?></h4>
                        <div class="field">
                            <label>Variant B Title</label>
                            <input type="text" name="ab_title_b" id="edit-link-ab-title" placeholder="Test a different headline" <?php if(!$is_pro) echo 'disabled'; ?>>
                        </div>
                        <div class="field">
                            <label>Variant B URL</label>
                            <input type="url" name="ab_url_b" id="edit-link-ab-url" placeholder="Test a different destination" <?php if(!$is_pro) echo 'disabled'; ?>>
                        </div>
                        <small>If set, visitors will randomly see either Variant A or B.</small>
                    </div>
                    <div id="routing-settings" class="field-row <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>">
                        <h4>Conditional Routing <?php if(!$is_pro) echo '🔒'; ?></h4>
                        <div class="field">
                            <label>Mobile-only URL</label>
                            <input type="url" name="url_mobile" id="edit-link-mobile" placeholder="Leave empty for default" <?php if(!$is_pro) echo 'disabled'; ?>>
                            <small class="helper-note">Send mobile users to a different destination (e.g. App Store).</small>
                        </div>
                        <div class="field">
                            <label>Geo-targeted URL</label>
                            <input type="url" name="url_geo" id="edit-link-geo" placeholder="e.g. US Specific link" <?php if(!$is_pro) echo 'disabled'; ?>>
                        </div>
                        <div class="field">
                            <label>Target Country Code (ISO, e.g. US)</label>
                            <input type="text" name="url_geo_country" id="edit-link-geo-country" placeholder="US" <?php if(!$is_pro) echo 'disabled'; ?>>
                            <small class="helper-note">Enter 2-letter country code (US, UK, CA, etc.)</small>
                        </div>
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
                        <label>Extra Content / Features (one per line for pricing)</label>
                        <textarea name="extra" id="edit-link-extra" rows="4"></textarea>
                        <small class="helper-note">For Pricing blocks, put one feature per line.</small>
                    </div>
                    <div class="field-row" style="display:flex; gap:10px;">
                        <div class="field" style="flex:1;">
                            <label>Password Protect <?php if(!$is_pro) echo '🔒'; ?></label>
                            <input type="text" name="link_password" id="edit-link-pass" placeholder="Pro only" <?php if(!$is_pro) echo 'disabled'; ?>>
                        </div>
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
                    <div class="field-row <?php echo $is_pro ? '' : 'pro-gated-inline'; ?>" style="display:flex; gap:10px; background:#eef9ff; padding:15px; border-radius:12px;">
                        <div class="field">
                            <label>Day-Hour: From <?php if(!$is_pro) echo '🔒'; ?></label>
                            <input type="number" name="hour_from" id="edit-link-hour-from" min="0" max="23" placeholder="0" <?php if(!$is_pro) echo 'disabled'; ?>>
                        </div>
                        <div class="field">
                            <label>Day-Hour: To <?php if(!$is_pro) echo '🔒'; ?></label>
                            <input type="number" name="hour_to" id="edit-link-hour-to" min="0" max="23" placeholder="23" <?php if(!$is_pro) echo 'disabled'; ?>>
                        </div>
                        <small style="display:block; width:100%;">Show this link only during specific hours (0-23). Great for "Live Support" or "Lunch Specials".</small>
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
