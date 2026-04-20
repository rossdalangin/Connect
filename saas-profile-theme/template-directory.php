<?php
/**
 * Template Name: Discovery Directory
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<div class="directory-container" style="max-width:1200px; margin:60px auto; padding:0 20px;">
    <div style="text-align:center; margin-bottom:60px;">
        <h1 style="font-size:3rem; font-weight:900;">Discover Elite Creators</h1>
        <p style="font-size:1.2rem; color:#666;">Explore the best digital identities built with our platform.</p>
    </div>

    <div class="directory-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:30px;">
        <?php
        $profiles = get_posts([
            'post_type' => 'saas_profile',
            'post_status' => 'publish',
            'meta_query' => [
                ['key' => '_saas_show_in_directory', 'value' => '1']
            ],
            'numberposts' => 50
        ]);

        if($profiles) :
            foreach($profiles as $p) :
                $p_meta = saas_get_profile_meta($p->ID);
                ?>
                <a href="<?php echo home_url('/' . $p->post_name); ?>" class="profile-card" style="text-decoration:none; color:inherit; background:#fff; border-radius:24px; padding:30px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.05); transition:transform 0.3s ease;">
                    <div style="margin-bottom:20px;">
                        <?php echo get_the_post_thumbnail($p->ID, 'thumbnail', ['style' => 'width:100px; height:100px; border-radius:50%; object-fit:cover; border:4px solid var(--primary-color);']); ?>
                    </div>
                    <h3 style="margin:0; font-size:1.4rem; font-weight:800;"><?php echo esc_html($p->post_title); ?></h3>
                    <p style="font-size:0.9rem; color:#888; margin:10px 0;"><?php echo esc_html($p_meta['headline']); ?></p>
                    <div style="margin-top:20px; font-weight:bold; color:var(--primary-color);">View Profile →</div>
                </a>
                <style>
                .profile-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
                </style>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align:center; color:#999;">No public profiles found. Be the first to join!</p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
