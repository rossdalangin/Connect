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
        while ( have_posts() ) : the_post();
            the_title('<h1>', '</h1>');
            the_content();
        endwhile;
    else :
        echo "<h1>Page not found.</h1>";
    endif;
    include __DIR__ . '/footer.php';
    return;
}

$profile_id = $profile->ID;
$user_id = $profile->post_author;
$meta = saas_get_profile_meta( $profile_id );
$bg_type = get_post_meta( $profile_id, '_saas_bg_type', true ) ?: 'flat';
$bg_color = get_post_meta( $profile_id, '_saas_bg_color', true ) ?: '#f3f3f1';
$gradient = get_post_meta( $profile_id, '_saas_bg_gradient', true );
$btn_shape = get_post_meta( $profile_id, '_saas_btn_shape', true ) ?: 'pill';
$font_family = get_post_meta( $profile_id, '_saas_font_family', true ) ?: "'Inter', sans-serif";
$shadow_style = get_post_meta( $profile_id, '_saas_container_shadow', true ) ?: 'soft';

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
    <!-- Header Block -->
    <header class="profile-header">
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
        <?php foreach ( $blocks as $block ) :
            $type = get_post_meta( $block->ID, '_saas_block_type', true ) ?: 'button';
            $style = get_post_meta( $block->ID, '_saas_block_style', true ) ?: 'regular';
            $base_url = get_post_meta( $block->ID, '_saas_link_url', true );
            $url = saas_get_effective_url( $block->ID, $base_url ); // Device/Geo Routing
            ?>
            <div class="saas-block block-<?php echo esc_attr($type); ?> style-<?php echo esc_attr($style); ?>">
                <?php if ($type === 'button') : ?>
                    <a href="<?php echo esc_url( $url ); ?>"
                       class="saas-link-btn"
                       data-link-id="<?php echo $block->ID; ?>"
                       onclick="saasTrackClick(<?php echo $block->ID; ?>)">
                        <?php echo esc_html( $block->post_title ); ?>
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
                        foreach ($socials as $platform => $p_url) : ?>
                            <a href="<?php echo esc_url($p_url); ?>" class="social-icon" target="_blank">
                                <span><?php echo esc_html(ucfirst($platform)); ?></span>
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
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Lead Funnel Block -->
    <section class="lead-form-section">
        <h3><?php echo esc_html( get_post_meta( $profile_id, '_saas_lead_title', true ) ?: 'Contact Me' ); ?></h3>
        <form id="lead-form">
            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
            <input type="hidden" name="security" value="<?php echo wp_create_nonce('saas_lead_nonce'); ?>">
            <div style="display:none;"><input type="text" name="saas_honeypot"></div> <!-- Spam Honeypot -->
            <div class="input-group">
                <input type="text" name="name" placeholder="Your Name" required>
            </div>
            <div class="input-group">
                <input type="email" name="email" placeholder="Your Email" required>
            </div>
            <button type="submit">Submit Request</button>
        </form>
        <div id="lead-feedback"></div>
    </section>

    <!-- vCard Block (Sticky) -->
    <div class="social-share-buttons">
        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(home_url($slug)); ?>" target="_blank">𝕏</a>
        <a href="https://wa.me/?text=<?php echo urlencode(home_url($slug)); ?>" target="_blank">WhatsApp</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(home_url($slug)); ?>" target="_blank">FB</a>
    </div>

    <div class="sticky-cta">
        <a href="<?php echo home_url('/?saas_action=vcard&profile=' . $profile_id); ?>" class="save-contact-btn">
            💾 Save Contact Info
        </a>
    </div>
</div>

<script>
// Track Profile View on Load
document.addEventListener('DOMContentLoaded', function() {
    saasTrackEvent('view', <?php echo $profile_id; ?>);
});

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

// Lead form handling via AJAX
document.getElementById('lead-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const feedback = document.getElementById('lead-feedback');
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
</script>

<?php include __DIR__ . '/footer.php'; ?>
