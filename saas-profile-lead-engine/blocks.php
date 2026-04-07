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

    // Register the Lead Capture Form block
    register_block_type( 'saas/lead-form-block', [
        'editor_script' => 'saas-block-editor-js',
        'render_callback' => 'saas_render_lead_form_block',
        'attributes' => [
            'profile_id' => [
                'type' => 'number',
                'default' => 0
            ],
            'title' => [
                'type' => 'string',
                'default' => 'Contact Me'
            ]
        ]
    ]);
}
add_action( 'init', 'saas_register_gutenberg_blocks' );

/**
 * Render Callback for the profile embed block
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

/**
 * Render Callback for the lead form block
 */
function saas_render_lead_form_block( $attributes ) {
    $profile_id = $attributes['profile_id'];
    $title = $attributes['title'];
    if ( ! $profile_id ) return '<p>Please select a SaaS Profile to link this form to.</p>';

    ob_start();
    ?>
    <section class="saas-block block-lead-form saas-embedded-lead-form" style="padding:40px; background:#f9f9f9; border-radius:24px; border:1px solid #eee;">
        <h3 style="margin-top:0;"><?php echo esc_html( $title ); ?></h3>
        <form class="saas-dynamic-form" data-block-id="embedded">
            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
            <input type="hidden" name="security" value="<?php echo wp_create_nonce('saas_lead_nonce'); ?>">
            <div style="display:none;"><input type="text" name="saas_honeypot"></div>
            <div class="input-group" style="margin-bottom:15px;">
                <input type="text" name="name" placeholder="Your Name" required style="width:100%; padding:12px; border-radius:8px; border:1px solid #ddd;">
            </div>
            <div class="input-group" style="margin-bottom:15px;">
                <input type="email" name="email" placeholder="Your Email" required style="width:100%; padding:12px; border-radius:8px; border:1px solid #ddd;">
            </div>
            <button type="submit" style="width:100%; padding:15px; background:#6c5ce7; color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">Submit Request</button>
        </form>
        <div class="lead-feedback" style="margin-top:15px; font-weight:bold;"></div>
    </section>
    <?php
    return ob_get_clean();
}
