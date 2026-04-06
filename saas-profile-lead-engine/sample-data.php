<?php
/**
 * Sample Data Generator Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Sample_Data {
    public static function generate() {
        $user_id = get_current_user_id();

        // 1. Create a Sample Profile
        $profile_id = wp_insert_post([
            'post_type'   => 'saas_profile',
            'post_title'  => 'Alex SaaS Expert',
            'post_status' => 'publish',
            'post_name'   => 'alex-expert',
            'post_author' => $user_id,
        ]);

        update_post_meta($profile_id, '_saas_headline', 'Building the Future of Digital Identity');
        update_post_meta($profile_id, '_saas_bio', 'Senior Architect & Full Stack Engineer. Lover of clean code and high-converting systems.');
        update_post_meta($profile_id, '_saas_theme_color', '#39e09b');
        update_post_meta($profile_id, '_saas_bg_type', 'gradient');
        update_post_meta($profile_id, '_saas_bg_gradient', 'linear-gradient(135deg, #39e09b 0%, #20b2aa 100%)');

        // 2. Add Sample Blocks
        $blocks = [
            ['title' => '👉 Join My Newsletter', 'url' => 'https://newsletter.alex.com', 'type' => 'button', 'style' => 'featured'],
            ['title' => 'Watch My Latest Keynote', 'url' => 'https://youtube.com/watch?v=123', 'type' => 'video', 'style' => 'regular'],
            ['title' => 'How much do you charge?', 'url' => '#', 'type' => 'faq', 'style' => 'regular'],
            ['title' => 'Pro Plan', 'url' => 'https://stripe.com/checkout', 'type' => 'pricing', 'style' => 'glow'],
            ['title' => 'My Portfolio', 'url' => '#', 'type' => 'image_gallery', 'style' => 'regular'],
        ];

        foreach ($blocks as $index => $b) {
            $link_id = wp_insert_post([
                'post_type'   => 'saas_link',
                'post_title'  => $b['title'],
                'post_status' => 'publish',
                'post_author' => $user_id,
            ]);
            update_post_meta($link_id, '_saas_block_type', $b['type']);
            update_post_meta($link_id, '_saas_block_style', $b['style']);
            update_post_meta($link_id, '_saas_link_url', $b['url']);
            update_post_meta($link_id, '_saas_priority', $index);
            if ($b['type'] === 'faq') {
                update_post_meta($link_id, '_saas_faq_answer', 'I offer tiered pricing based on project scope. Contact me for a custom quote.');
            } elseif ($b['type'] === 'pricing') {
                update_post_meta($link_id, '_saas_price', '$19/mo');
                update_post_meta($link_id, '_saas_features', ['Priority Support', 'No Ads', 'API Access']);
            } elseif ($b['type'] === 'image_gallery') {
                update_post_meta($link_id, '_saas_gallery_images', [
                    'https://via.placeholder.com/300',
                    'https://via.placeholder.com/301',
                    'https://via.placeholder.com/302',
                    'https://via.placeholder.com/303'
                ]);
            }
        }

        // 3. Add Sample Leads
        for ($i=1; $i<=3; $i++) {
            $lead_id = wp_insert_post([
                'post_type'   => 'saas_lead',
                'post_title'  => "Sample Lead $i",
                'post_status' => 'publish',
                'post_author' => $user_id,
            ]);
            update_post_meta($lead_id, '_saas_lead_name', "User $i");
            update_post_meta($lead_id, '_saas_lead_email', "user$i@example.com");
            update_post_meta($lead_id, '_saas_lead_source_id', $profile_id);
        }

        // 4. Add Sample Analytics
        global $wpdb;
        $table = $wpdb->prefix . 'saas_analytics';
        for ($i=0; $i<10; $i++) {
            $wpdb->insert($table, [
                'user_id'    => $user_id,
                'event_type' => ($i % 2 == 0) ? 'view' : 'click',
                'target_id'  => ($i % 2 == 0) ? $profile_id : $link_id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
