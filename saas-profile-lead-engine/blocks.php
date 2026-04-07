<?php
/**
 * Gutenberg Block Integration (SaaS Profile Embed)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function saas_register_gutenberg_blocks() {
    // Enqueue Block Editor Script
    wp_register_script(
        'saas-block-editor-js',
        plugin_dir_url( __FILE__ ) . 'block-editor.js',
        [ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
    );

    // Register the SaaS Profile Embed block
    register_block_type( 'saas/profile-embed', [
        'editor_script' => 'saas-block-editor-js',
        'render_callback' => 'saas_render_profile_block',
        'attributes' => [
            'profile_id' => [
                'type' => 'number',
                'default' => 0
            ]
        ]
    ]);
}
add_action( 'init', 'saas_register_gutenberg_blocks' );

/**
 * Render Callback for the block
 */
function saas_render_profile_block( $attributes ) {
    $profile_id = $attributes['profile_id'];
    if ( ! $profile_id ) return '<p>Please select a SaaS Profile to embed.</p>';

    $profile = get_post( $profile_id );
    if ( ! $profile || $profile->post_type !== 'saas_profile' ) return '';

    // Simplified embed rendering (reuse theme logic if possible)
    ob_start();
    ?>
    <div class="saas-profile-embed" style="border:1px solid #ddd; padding:20px; border-radius:12px;">
        <h3><?php echo esc_html($profile->post_title); ?></h3>
        <a href="<?php echo home_url('/' . $profile->post_name); ?>" class="saas-link-btn" style="display:inline-block; padding:10px 20px; background:#0073aa; color:#fff; text-decoration:none; border-radius:6px;">View Full Profile</a>
    </div>
    <?php
    return ob_get_clean();
}
