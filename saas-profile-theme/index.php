<?php
/**
 * Main Template - Public Profile Rendering Engine (Enhanced for Modular Blocks)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Get Profile from Query Var
$slug = get_query_var( 'saas_profile' );
$profile = null;

if ( $slug ) {
    $profile = get_posts([
        'name'        => $slug,
        'post_type'   => 'saas_profile',
        'post_status' => 'publish',
        'numberposts' => 1
    ]);
    $profile = ! empty($profile) ? $profile[0] : null;
}

// If it's not a profile, fallback to standard WP loop (Theme as Active Theme support)
if ( ! $profile ) {
    include __DIR__ . '/header.php';
    if ( have_posts() ) :
        while ( have_posts() ) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('standard-page-container'); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title" style="font-size: 2.5rem; margin-bottom: 40px; text-align: center;">', '</h1>' ); ?>
                </header>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile;
    else :
        echo "<div class='standard-page-container'><h1>Page not found.</h1></div>";
    endif;
    include __DIR__ . '/footer.php';
    return;
}

$profile_id = $profile->ID;
$user_id = $profile->post_author;
$meta = saas_get_profile_meta( $profile_id );

// Check Pro Status (Unified License Check)
$is_pro = saas_is_profile_licensed($profile_id);
$bg_type = get_post_meta( $profile_id, '_saas_bg_type', true ) ?: 'flat';
$bg_color = get_post_meta( $profile_id, '_saas_bg_color', true ) ?: '#f3f3f1';
$gradient = get_post_meta( $profile_id, '_saas_bg_gradient', true );
$btn_shape = get_post_meta( $profile_id, '_saas_btn_shape', true ) ?: 'pill';
$font_family = get_post_meta( $profile_id, '_saas_font_family', true ) ?: "'Inter', sans-serif";
$shadow_style = get_post_meta( $profile_id, '_saas_container_shadow', true ) ?: 'soft';
$profile_theme = get_post_meta($profile_id, '_saas_profile_theme', true) ?: 'light';

// Fetch Links (Modular Blocks)
$blocks = get_posts([
    'post_type'   => 'saas_link',
    'meta_query' => [
        ['key' => '_saas_profile_id', 'value' => $profile_id]
    ],
    'orderby'     => 'meta_value_num',
    'meta_key'    => '_saas_priority',
    'order'       => 'ASC',
    'numberposts' => -1,
]);

// Include Header
if ( ! defined('ABSPATH') ) exit;
$theme_class = 'theme-' . $profile_theme;
include __DIR__ . '/header.php';
?>

<style>
    :root {
        --primary-color: <?php echo esc_attr( $meta['theme_color'] ); ?>;
        --bg-color: <?php echo esc_attr( $bg_color ); ?>;
        --btn-radius: <?php
            if ($btn_shape === 'pill') echo '50px';
            elseif ($btn_shape === 'rounded') echo '12px';
            else echo '0px';
        ?>;
        --font-family: <?php echo $font_family; ?>;
        --shadow-style: <?php
            if ($shadow_style === 'soft') echo '0 10px 30px rgba(0,0,0,0.05)';
            elseif ($shadow_style === 'hard') echo '8px 8px 0px #333';
            else echo 'none';
        ?>;
    }
    <?php
    $custom_css = get_post_meta($profile_id, '_saas_custom_css', true);
    if ($is_pro && $custom_css) echo $custom_css;
    ?>
    body {
        <?php if ($bg_type === 'gradient' && $gradient) : ?>
            background: <?php echo esc_attr($gradient); ?>;
        <?php else : ?>
            background-color: var(--bg-color);
        <?php endif; ?>
    }
</style>

<?php if ($is_pro && $bg_type === 'mesh') : ?>
    <div class="mesh-bg"></div>
<?php elseif ($is_pro && $bg_type === 'particles') : ?>
    <div id="particles-js"></div>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        particlesJS('particles-js', {
            "particles": { "number": { "value": 80 }, "color": { "value": "#ffffff" }, "opacity": { "value": 0.5 }, "size": { "value": 3 }, "line_linked": { "enable": true, "color": "#ffffff" }, "move": { "enable": true, "speed": 2 } },
            "interactivity": { "events": { "onhover": { "enable": true, "mode": "repulse" } } }
        });
    </script>
<?php endif; ?>

<div id="profile-container">
    <!-- Cover Banner -->
    <?php
    $cover_id = get_post_meta($profile_id, '_saas_cover_id', true);
    if ($cover_id) : ?>
        <div class="profile-cover">
            <?php echo wp_get_attachment_image($cover_id, 'large'); ?>
        </div>
    <?php endif; ?>

    <!-- Header Block -->
    <header class="profile-header <?php echo $cover_id ? 'has-cover' : ''; ?>">
        <?php if ( has_post_thumbnail( $profile_id ) ) : ?>
            <?php echo get_the_post_thumbnail( $profile_id, 'thumbnail' ); ?>
        <?php else : ?>
            <img src="https://via.placeholder.com/150" alt="Avatar">
        <?php endif; ?>
        <h1>
            <?php echo esc_html( $profile->post_title ); ?>
            <?php if ($is_pro && get_post_meta($profile_id, '_saas_verified_badge', true)) : ?>
                <span class="verified-badge" title="Verified Professional" style="color:#1d9bf0; font-size:0.8em; margin-left:5px;">✅</span>
            <?php endif; ?>
        </h1>
        <p class="headline"><?php echo esc_html( $meta['headline'] ); ?></p>
        <p class="bio"><?php echo nl2br( esc_html( $meta['bio'] ) ); ?></p>
    </header>

    <!-- Dynamic Blocks Engine -->
    <div class="blocks-container">
        <?php foreach ( $blocks as $index => $block ) :
            $type = get_post_meta( $block->ID, '_saas_block_type', true ) ?: 'button';

            // Pro Gating Check
            $pro_blocks = ['image_gallery', 'newsletter', 'product', 'calendar'];
            if (in_array($type, $pro_blocks) && !$is_pro) continue;

            $style = get_post_meta( $block->ID, '_saas_block_style', true ) ?: 'regular';
            $animation = get_post_meta($block->ID, '_saas_block_animation', true) ?: 'fadeinup';
            $base_url = get_post_meta( $block->ID, '_saas_link_url', true );
            $url = saas_get_effective_url( $block->ID, $base_url ); // Device/Geo Routing
            $custom_bg = get_post_meta($block->ID, '_saas_custom_bg', true);
            $custom_text = get_post_meta($block->ID, '_saas_custom_text', true);
            $block_style_attr = '';
            if ($custom_bg) $block_style_attr .= "background-color: $custom_bg; ";
            if ($custom_text) $block_style_attr .= "color: $custom_text; ";

            // Scheduling Check
            $start_date = get_post_meta($block->ID, '_saas_start_date', true);
            $end_date = get_post_meta($block->ID, '_saas_end_date', true);
            $now = time();
            if ($start_date && strtotime($start_date) > $now) continue;
            if ($end_date && strtotime($end_date) < $now) continue;

            // Hour-based scheduling
            $hour_from = get_post_meta($block->ID, '_saas_hour_from', true);
            $hour_to   = get_post_meta($block->ID, '_saas_hour_to', true);
            if ($is_pro && ($hour_from !== '' || $hour_to !== '')) {
                $current_hour = (int) current_time('G');
                if ($hour_from !== '' && $current_hour < (int)$hour_from) continue;
                if ($hour_to !== '' && $current_hour > (int)$hour_to) continue;
            }
            ?>
            <div class="saas-block block-<?php echo esc_attr($type); ?> style-<?php echo esc_attr($style); ?> animate-<?php echo esc_attr($animation); ?>" data-block-id="<?php echo $block->ID; ?>" style="animation-delay: <?php echo $index * 0.1; ?>s; <?php echo $block_style_attr; ?>">
                <?php if ($type === 'button') :
                    $has_pass = !empty(get_post_meta($block->ID, '_saas_link_password', true));

                    // A/B Split Testing Logic
                    $ab_title_b = get_post_meta($block->ID, '_saas_ab_title_b', true);
                    $ab_url_b   = get_post_meta($block->ID, '_saas_ab_url_b', true);
                    $variant    = 'a';

                    if ($is_pro && $ab_title_b && $ab_url_b) {
                        $variant = (rand(0, 1) === 1) ? 'b' : 'a';
                        if ($variant === 'b') {
                            $block->post_title = $ab_title_b;
                            $url = saas_get_effective_url($block->ID, $ab_url_b);
                        }
                    }
                    ?>
                    <a href="<?php echo esc_url( $url ); ?>"
                       class="saas-link-btn"
                       style="<?php echo $block_style_attr; ?>"
                       data-link-id="<?php echo $block->ID; ?>"
                       data-variant="<?php echo $variant; ?>"
                       onclick="return saasCheckLink(event, <?php echo $block->ID; ?>, <?php echo $has_pass ? 'true' : 'false'; ?>, '<?php echo $variant; ?>')">
                        <?php
                        $thumb_id = get_post_meta($block->ID, '_saas_link_image_id', true);
                        if ($thumb_id) : ?>
                            <img src="<?php echo esc_url(wp_get_attachment_thumb_url($thumb_id)); ?>" class="btn-thumb">
                        <?php endif; ?>
                        <span class="btn-label"><?php echo esc_html( $block->post_title ); ?> <?php if($has_pass) echo '🔒'; ?></span>
                    </a>
                <?php elseif ($type === 'video') : ?>
                    <div class="video-embed">
                        <?php echo wp_oembed_get( $url ); ?>
                    </div>
                <?php elseif ($type === 'testimonial') : ?>
                    <div class="testimonial-block">
                        <p class="quote">"<?php echo esc_html( get_post_meta($block->ID, '_saas_testimonial_text', true) ); ?>"</p>
                        <cite>- <?php echo esc_html( $block->post_title ); ?></cite>
                    </div>
                <?php elseif ($type === 'faq') : ?>
                    <details class="faq-block">
                        <summary><?php echo esc_html( $block->post_title ); ?></summary>
                        <p><?php echo esc_html( get_post_meta($block->ID, '_saas_faq_answer', true) ); ?></p>
                    </details>
                <?php elseif ($type === 'pricing') : ?>
                    <div class="pricing-card">
                        <h3><?php echo esc_html( $block->post_title ); ?></h3>
                        <div class="price"><?php echo esc_html( get_post_meta($block->ID, '_saas_price', true) ); ?></div>
                        <ul>
                            <?php
                            $features = get_post_meta($block->ID, '_saas_features', true) ?: [];
                            foreach ($features as $feature) : ?>
                                <li>✓ <?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo esc_url($url); ?>" class="saas-link-btn">Select Plan</a>
                    </div>
                <?php elseif ($type === 'image_gallery') : ?>
                    <div class="image-gallery-block">
                        <h3><?php echo esc_html($block->post_title); ?></h3>
                        <div class="gallery-grid">
                            <?php
                            $images = get_post_meta($block->ID, '_saas_gallery_images', true) ?: [];
                            foreach ($images as $img_url) : ?>
                                <img src="<?php echo esc_url($img_url); ?>" alt="Gallery Image">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($type === 'calendar') : ?>
                    <div class="calendar-block">
                        <h3><?php echo esc_html($block->post_title); ?></h3>
                        <div class="calendar-embed">
                            <iframe src="<?php echo esc_url($url); ?>" width="100%" height="400" frameborder="0"></iframe>
                        </div>
                    </div>
                <?php elseif ($type === 'social_icons') : ?>
                    <div class="social-icons-block">
                        <?php
                        $socials = get_post_meta($block->ID, '_saas_social_data', true) ?: [];
                        foreach ($socials as $platform => $p_url) :
                            $icon_map = [
                                'instagram' => '📸', 'facebook' => '👥', 'twitter' => '🐦', 'x' => '𝕏',
                                'linkedin' => '💼', 'youtube' => '🎥', 'whatsapp' => '💬', 'tiktok' => '🎵',
                                'email' => '✉️', 'phone' => '📞', 'website' => '🌐'
                            ];
                            $icon = $icon_map[strtolower($platform)] ?? '🔗';
                            ?>
                            <a href="<?php echo esc_url($p_url); ?>" class="social-icon social-<?php echo esc_attr(strtolower($platform)); ?>" target="_blank" title="<?php echo esc_attr(ucfirst($platform)); ?>">
                                <span class="si-icon"><?php echo $icon; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($type === 'countdown') : ?>
                    <div class="countdown-block" data-expiry="<?php echo esc_attr(get_post_meta($block->ID, '_saas_expiry', true)); ?>">
                        <div class="timer-title"><?php echo esc_html($block->post_title); ?></div>
                        <div class="timer-display">00:00:00:00</div>
                    </div>
                <?php elseif ($type === 'newsletter') : ?>
                    <div class="newsletter-block">
                        <h3><?php echo esc_html($block->post_title); ?></h3>
                        <form class="newsletter-form">
                            <input type="email" placeholder="Email Address" required>
                            <button type="submit">Join</button>
                        </form>
                    </div>
                <?php elseif ($type === 'milestone') : ?>
                    <div class="milestone-block">
                        <div class="ms-title"><?php echo esc_html($block->post_title); ?></div>
                        <div class="ms-bar-bg">
                            <div class="ms-bar-fill" style="width: <?php echo esc_attr(get_post_meta($block->ID, '_saas_ms_percent', true) ?: '50'); ?>%;"></div>
                        </div>
                        <div class="ms-label"><?php echo esc_html(get_post_meta($block->ID, '_saas_ms_label', true) ?: 'Progress'); ?></div>
                    </div>
                <?php elseif ($type === 'product') : ?>
                    <div class="product-block">
                        <div class="product-info">
                            <h4><?php echo esc_html($block->post_title); ?></h4>
                            <div class="product-price"><?php echo esc_html(get_post_meta($block->ID, '_saas_price', true) ?: '$0'); ?></div>
                        </div>
                        <form class="product-checkout-form">
                            <input type="hidden" name="block_id" value="<?php echo $block->ID; ?>">
                            <input type="hidden" name="plan_id" value="product_<?php echo $block->ID; ?>">
                            <button type="submit" class="saas-link-btn product-cta" style="border:none; cursor:pointer;">Buy Now</button>
                        </form>
                    </div>
                <?php elseif ($type === 'social_feed') : ?>
                    <div class="social-feed-block">
                        <div style="border:1px dashed #ccc; padding:40px; border-radius:12px; background:rgba(0,0,0,0.02);">
                            <p style="margin:0; font-weight:bold;"><?php echo esc_html($block->post_title); ?> Feed</p>
                            <p style="font-size:0.8rem; color:#888;">Embed for <?php echo esc_url($url); ?> will appear here.</p>
                        </div>
                    </div>
                <?php elseif ($type === 'lead_form') : ?>
                    <section class="lead-form-section block-lead-form">
                        <h3><?php echo esc_html( $block->post_title ?: 'Contact Me' ); ?></h3>
                        <form class="saas-dynamic-form" data-block-id="<?php echo $block->ID; ?>">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <input type="hidden" name="block_id" value="<?php echo $block->ID; ?>">
                            <input type="hidden" name="security" value="<?php echo wp_create_nonce('saas_lead_nonce'); ?>">
                            <div style="display:none;"><input type="text" name="saas_honeypot"></div>
                            <div class="input-group">
                                <input type="text" name="name" placeholder="Your Name" required>
                            </div>
                            <div class="input-group">
                                <input type="email" name="email" placeholder="Your Email" required>
                            </div>
                            <?php if (get_post_meta($profile_id, '_saas_form_phone', true)) : ?>
                                <div class="input-group">
                                    <input type="text" name="phone" placeholder="<?php echo esc_attr(get_post_meta($profile_id, '_saas_form_label_phone', true) ?: 'Phone Number'); ?>" <?php if(get_post_meta($profile_id, '_saas_form_req_phone', true)) echo 'required'; ?>>
                                </div>
                            <?php endif; ?>
                            <?php if (get_post_meta($profile_id, '_saas_form_msg', true)) : ?>
                                <div class="input-group">
                                    <textarea name="message" placeholder="<?php echo esc_attr(get_post_meta($profile_id, '_saas_form_label_msg', true) ?: 'Your Message'); ?>" rows="3" <?php if(get_post_meta($profile_id, '_saas_form_req_msg', true)) echo 'required'; ?>></textarea>
                                </div>
                            <?php endif; ?>
                            <button type="submit">Submit Request</button>
                        </form>
                        <div class="lead-feedback"></div>
                    </section>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- vCard Block (Sticky) -->
    <div class="social-share-buttons">
        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(home_url($slug)); ?>" target="_blank">𝕏</a>
        <a href="https://wa.me/?text=<?php echo urlencode(home_url($slug)); ?>" target="_blank">WhatsApp</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(home_url($slug)); ?>" target="_blank">FB</a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(home_url($slug)); ?>" target="_blank">LI</a>
    </div>

    <?php
    $social_proof = get_post_meta($profile_id, '_saas_social_proof', true);
    if ($social_proof && $is_pro) :
        $analytics = new Saas_Analytics();
        $summary = $analytics->get_user_summary($user_id);
    ?>
        <div class="social-proof-bubble animate-bouncein">
            👁️ <?php echo number_format($summary['views'] + 100); ?> people visited recently
        </div>
    <?php endif; ?>

    <div class="sticky-cta">
        <a href="<?php echo home_url('/?saas_action=vcard&profile=' . $profile_id); ?>" class="save-contact-btn">
            💾 Save Contact Info
        </a>
    </div>

    <!-- FOMO Activity Popups (Elite Pro) -->
    <?php
    $fomo_enabled = get_post_meta($profile_id, '_saas_fomo_popups', true);
    if ($is_pro && $fomo_enabled) :
        $analytics = new Saas_Analytics();
        $recent_leads = $analytics->get_recent_leads($profile_id);
        if ($recent_leads) :
        ?>
        <div id="saas-fomo-popup" class="fomo-hidden">
            <div class="fomo-icon">🚀</div>
            <div class="fomo-content">
                <p id="fomo-text"></p>
                <small id="fomo-time"></small>
            </div>
        </div>
        <script>
            const recentLeads = <?php echo json_encode($recent_leads); ?>;
            let currentLeadIdx = 0;
            const popup = document.getElementById('saas-fomo-popup');
            const pText = document.getElementById('fomo-text');
            const pTime = document.getElementById('fomo-time');

            function showNextFomo() {
                const lead = recentLeads[currentLeadIdx];
                pText.innerHTML = `<strong>${lead.name}</strong> just inquired!`;
                pTime.innerText = lead.time;
                popup.classList.remove('fomo-hidden');
                popup.classList.add('fomo-visible');

                setTimeout(() => {
                    popup.classList.remove('fomo-visible');
                    popup.classList.add('fomo-hidden');
                    currentLeadIdx = (currentLeadIdx + 1) % recentLeads.length;
                    setTimeout(showNextFomo, 5000);
                }, 4000);
            }
            setTimeout(showNextFomo, 3000);
        </script>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Growth Branding (Hide for Pro) -->
    <?php
    $hide_branding = get_post_meta($profile_id, '_saas_hide_branding', true);
    $footer_text   = get_post_meta($profile_id, '_saas_footer_text', true);

    if ($is_pro && $hide_branding) : ?>
        <div class="saas-growth-branding" style="margin-top:40px; padding-bottom:120px; opacity:0.6; font-size:0.8rem;">
            <?php echo esc_html($footer_text ?: ''); ?>
        </div>
    <?php else : ?>
        <div class="saas-growth-branding" style="margin-top:40px; padding-bottom:120px; opacity:0.6; font-size:0.8rem;">
            <a href="<?php echo home_url('/?ref=' . $slug); ?>" style="text-decoration:none; color:inherit; font-weight:800;">
                Powered by <?php echo get_bloginfo('name'); ?> 🚀
            </a>
        </div>
    <?php endif; ?>

    <!-- Mobile Navigation Bar -->
    <nav class="profile-bottom-nav">
        <a href="#profile-container" title="Top">🏠</a>
        <a href="mailto:<?php echo get_the_author_meta('user_email', $user_id); ?>" title="Email">✉️</a>
        <a href="<?php echo home_url('/register'); ?>" title="Create Yours">➕</a>
        <a href="#" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;" title="Share">📤</a>
    </nav>

    <!-- Link Password Modal -->
    <div id="link-password-modal" class="saas-theme-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-inner" style="background:#fff; padding:40px; border-radius:24px; max-width:400px; width:90%; text-align:center;">
            <div style="font-size:3rem; margin-bottom:20px;">🔒</div>
            <h3>Password Required</h3>
            <p>This content is protected. Please enter the password to continue.</p>
            <form id="saas-pass-form" style="margin-top:20px;">
                <input type="hidden" id="modal-link-id">
                <input type="password" id="modal-pass-input" placeholder="Enter Password" style="width:100%; padding:15px; border-radius:12px; border:1px solid #ddd; margin-bottom:15px; box-sizing:border-box;">
                <button type="submit" style="width:100%; padding:15px; background:var(--primary-color); color:#fff; border:none; border-radius:12px; font-weight:bold; cursor:pointer;">Unlock Content</button>
            </form>
            <button class="close-pass-modal" style="margin-top:20px; background:none; border:none; color:#999; cursor:pointer;">Cancel</button>
        </div>
    </div>
</div>

<?php
// Profile Password Protection Logic
$profile_pass = get_post_meta($profile_id, '_saas_profile_password', true);
if ($is_pro && $profile_pass) : ?>
    <div id="profile-gate" style="position:fixed; top:0; left:0; width:100%; height:100%; background:#fff; z-index:99999; display:flex; align-items:center; justify-content:center; text-align:center;">
        <div style="max-width:400px; padding:40px;">
            <div style="font-size:4rem; margin-bottom:20px;">🔐</div>
            <h2>Private Profile</h2>
            <p>Please enter the password to view this digital identity.</p>
            <form id="profile-gate-form">
                <input type="password" id="gate-pass" placeholder="Password" required style="width:100%; padding:15px; border-radius:12px; border:1px solid #ddd; margin-bottom:15px;">
                <button type="submit" style="width:100%; padding:15px; background:var(--primary-color); color:#fff; border:none; border-radius:12px; font-weight:bold; cursor:pointer;">Unlock Profile</button>
            </form>
            <div id="gate-error" style="color:red; margin-top:10px; display:none;">Incorrect password.</div>
        </div>
    </div>
    <script>
    document.getElementById('profile-gate-form').onsubmit = (e) => {
        e.preventDefault();
        const pass = document.getElementById('gate-pass').value;
        if (pass === '<?php echo esc_js($profile_pass); ?>') {
            document.getElementById('profile-gate').style.display = 'none';
            document.body.style.overflow = 'auto';
        } else {
            document.getElementById('gate-error').style.display = 'block';
        }
    };
    document.body.style.overflow = 'hidden';
    </script>
<?php endif; ?>

<script>
// Track Profile View on Load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const eventType = urlParams.get('src') === 'nfc' ? 'nfc_tap' : 'view';
    saasTrackEvent(eventType, <?php echo $profile_id; ?>);

    // Instant vCard Logic (for NFC/Premium users)
    if (urlParams.get('action') === 'vcard_auto' || urlParams.get('src') === 'nfc') {
        setTimeout(() => {
            window.location.href = '<?php echo home_url('/?saas_action=vcard&profile=' . $profile_id); ?>';
        }, 2000);
    }
});

// Password protection check (Modern Modal UI)
function saasCheckLink(e, linkId, hasPass, variant = 'a') {
    if (!hasPass) {
        saasTrackClick(linkId, variant);
        return true;
    }

    e.preventDefault();
    const modal = document.getElementById('link-password-modal');
    document.getElementById('modal-link-id').value = linkId;
    document.getElementById('modal-pass-input').value = '';
    modal.style.display = 'flex';

    return false;
}

document.querySelector('.close-pass-modal')?.addEventListener('click', () => {
    document.getElementById('link-password-modal').style.display = 'none';
});

document.getElementById('saas-pass-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const linkId = document.getElementById('modal-link-id').value;
    const pass = document.getElementById('modal-pass-input').value;
    const btn = this.querySelector('button');
    const originalText = btn.innerText;

    btn.innerText = 'Verifying...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'saas_verify_link_password');
    formData.append('link_id', linkId);
    formData.append('password', pass);

    fetch(saas_data.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            saasTrackClick(linkId);
            window.location.href = data.data.url;
        } else {
            alert(data.data);
            btn.innerText = originalText;
            btn.disabled = false;
        }
    });
});

// Analytics tracking
function saasTrackClick(linkId, variant = 'a') {
    const eventType = (variant === 'b') ? 'click_variant_b' : 'click';
    saasTrackEvent(eventType, linkId);
}

function saasTrackEvent(type, targetId) {
    fetch(saas_data.rest_url + '/track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            event: type,
            target_id: targetId
        })
    });
}

// Countdown Timer Logic
function saasInitCountdowns() {
    document.querySelectorAll('.countdown-block').forEach(block => {
        const expiryStr = block.dataset.expiry;
        if (!expiryStr) return;
        const expiry = new Date(expiryStr).getTime();
        const display = block.querySelector('.timer-display');

        const interval = setInterval(() => {
            const now = new Date().getTime();
            const distance = expiry - now;

            if (distance < 0) {
                clearInterval(interval);
                display.innerHTML = "EXPIRED";
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            display.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        }, 1000);
    });
}
document.addEventListener('DOMContentLoaded', saasInitCountdowns);

// Newsletter form handling via AJAX
document.querySelectorAll('.newsletter-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const block = this.closest('.saas-block');
        const emailInput = this.querySelector('input[type="email"]');
        const submitBtn = this.querySelector('button');
        const originalBtnText = submitBtn.innerText;

        submitBtn.innerText = 'Joining...';
        submitBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'saas_submit_lead');
        formData.append('email', emailInput.value);
        formData.append('name', 'Newsletter Subscriber');
        formData.append('profile_id', '<?php echo $profile_id; ?>');
        if (block.dataset.blockId) formData.append('block_id', block.dataset.blockId);
        formData.append('security', '<?php echo wp_create_nonce('saas_lead_nonce'); ?>');

        fetch(saas_data.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.innerHTML = '<p style="font-weight:bold; color:#fff; margin-top:10px;">✓ Subscribed successfully!</p>';
            } else {
                alert(data.data);
                submitBtn.innerText = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    });
});

// Real-Time Preview PostMessage Listener
window.addEventListener('message', function(event) {
    if (event.data.type === 'live_update') {
        const { key, value } = event.data;
        if (key === 'cover_update') {
            location.reload(); // Hard refresh for new images in preview
        }
        if (key === 'headline') document.querySelector('.profile-header h1').innerText = value;
        if (key === 'bio') document.querySelector('.profile-header .bio').innerText = value;
        if (key === 'theme_color') document.documentElement.style.setProperty('--primary-color', value);
        if (key === 'bg_value') {
            if (value.includes('gradient')) document.body.style.background = value;
            else document.body.style.backgroundColor = value;
        }
        if (key === 'profile_theme') {
            document.body.classList.remove('theme-light', 'theme-dark', 'theme-vibrant');
            document.body.classList.add('theme-' + value);
        }
        if (key === 'container_shadow') {
            let shadow = 'none';
            if (value === 'soft') shadow = '0 10px 30px rgba(0,0,0,0.05)';
            else if (value === 'hard') shadow = '8px 8px 0px #333';
            document.documentElement.style.setProperty('--shadow-style', shadow);
        }
    }
});

// Product Checkout handling
document.querySelectorAll('.product-checkout-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        btn.innerText = 'Redirecting...';
        btn.disabled = true;

        const formData = new FormData(this);
        formData.append('action', 'saas_checkout');
        formData.append('gateway', 'stripe'); // Default for products

        fetch(saas_data.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) window.location.href = data.data.redirect_url;
            else alert('Checkout failed');
        });
    });
});

// Lead form handling via AJAX (Dynamic Forms)
document.querySelectorAll('.saas-dynamic-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const feedback = this.nextElementSibling;
        const formData = new FormData(this);
        formData.append('action', 'saas_submit_lead');

        feedback.innerText = 'Sending...';

        fetch(saas_data.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            feedback.innerText = data.data.message;
            if (data.success) {
                this.reset();
                // Lead Magnet Delivery
                if (data.data.download) {
                    const a = document.createElement('a');
                    a.href = data.data.download;
                    a.download = '';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }
                if (data.data.redirect) window.location.href = data.data.redirect;
            }
        })
        .catch(err => {
            feedback.innerText = 'Error sending lead.';
        });
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
