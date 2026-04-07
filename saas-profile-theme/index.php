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

// Check Pro Status
$payments = new Saas_Payments();
$is_pro = $payments->is_pro_user($user_id);
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
    'post_author' => $user_id,
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
    body {
        <?php if ($bg_type === 'gradient' && $gradient) : ?>
            background: <?php echo esc_attr($gradient); ?>;
        <?php else : ?>
            background-color: var(--bg-color);
        <?php endif; ?>
    }
</style>

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
        <h1><?php echo esc_html( $profile->post_title ); ?></h1>
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
            ?>
            <div class="saas-block block-<?php echo esc_attr($type); ?> style-<?php echo esc_attr($style); ?> animate-<?php echo esc_attr($animation); ?>" data-block-id="<?php echo $block->ID; ?>" style="animation-delay: <?php echo $index * 0.1; ?>s; <?php echo $block_style_attr; ?>">
                <?php if ($type === 'button') :
                    $link_pass = get_post_meta($block->ID, '_saas_link_password', true);
                    ?>
                    <a href="<?php echo esc_url( $url ); ?>"
                       class="saas-link-btn"
                       style="<?php echo $block_style_attr; ?>"
                       data-link-id="<?php echo $block->ID; ?>"
                       onclick="return saasCheckLink(event, <?php echo $block->ID; ?>, '<?php echo esc_js($link_pass); ?>')">
                        <?php
                        $thumb_id = get_post_meta($block->ID, '_saas_link_image_id', true);
                        if ($thumb_id) : ?>
                            <img src="<?php echo esc_url(wp_get_attachment_thumb_url($thumb_id)); ?>" class="btn-thumb">
                        <?php endif; ?>
                        <span class="btn-label"><?php echo esc_html( $block->post_title ); ?> <?php if($link_pass) echo '🔒'; ?></span>
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
                        <a href="<?php echo esc_url($url); ?>" class="saas-link-btn product-cta">Buy Now</a>
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

    <!-- Mobile Navigation Bar -->
    <nav class="profile-bottom-nav">
        <a href="#profile-container" title="Top">🏠</a>
        <a href="mailto:<?php echo get_the_author_meta('user_email', $user_id); ?>" title="Email">✉️</a>
        <a href="<?php echo home_url('/register'); ?>" title="Create Yours">➕</a>
        <a href="#" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;" title="Share">📤</a>
    </nav>
</div>

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

// Password protection check
function saasCheckLink(e, linkId, pass) {
    if (!pass) return true;
    e.preventDefault();
    const input = prompt("This link is password protected. Enter password:");
    if (input === pass) {
        saasTrackClick(linkId);
        window.location.href = e.target.href;
    } else {
        alert("Incorrect password.");
    }
    return false;
}

// Analytics tracking
function saasTrackClick(linkId) {
    saasTrackEvent('click', linkId);
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
    }
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
