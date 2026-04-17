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

        <div class="directory-filters" style="margin-top:40px; display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
            <?php
            $active_niche = $_GET['niche'] ?? '';
            $niches = [
                '' => 'All Experts',
                'coach' => 'Coaches',
                'consultant' => 'Consultants',
                'realtor' => 'Real Estate',
                'artist' => 'Artists',
                'agency' => 'Agencies',
                'creator' => 'Creators'
            ];
            foreach($niches as $slug => $label) :
                $is_active = ($active_niche === $slug);
                $url = $slug ? add_query_arg('niche', $slug) : remove_query_arg('niche');
            ?>
                <a href="<?php echo esc_url($url); ?>" style="padding:12px 24px; border-radius:50px; text-decoration:none; font-weight:700; font-size:0.9rem; transition:all 0.3s; <?php echo $is_active ? 'background:var(--primary-color); color:#fff;' : 'background:#fff; border:1px solid #eee; color:#666;'; ?>">
                    <?php echo esc_html($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="directory-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:30px;">
        <?php
        $args = [
            'post_type' => 'saas_profile',
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_saas_show_in_directory', 'value' => '1']
            ],
            'numberposts' => 50
        ];

        if ($active_niche) {
            $args['meta_query'][] = ['key' => '_saas_niche', 'value' => $active_niche];
        }

        $profiles = get_posts($args);

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
