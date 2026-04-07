<?php
/**
 * Admin Profile & Link Manager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Admin_Profile_Manager {
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_profile_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_profile_meta' ] );

        // Add custom column to Profiles list to view/edit links
        add_filter( 'manage_saas_profile_posts_columns', [ $this, 'add_links_column' ] );
        add_action( 'manage_saas_profile_posts_custom_column', [ $this, 'render_links_column' ], 10, 2 );
    }

    public function add_profile_meta_boxes() {
        add_meta_box(
            'saas_profile_details',
            'SaaS Profile Configuration',
            [ $this, 'render_profile_meta_box' ],
            'saas_profile',
            'normal',
            'high'
        );
    }

    public function render_profile_meta_box( $post ) {
        $meta = saas_get_profile_meta( $post->ID );
        $headline = get_post_meta($post->ID, '_saas_headline', true);
        $bg_type = get_post_meta($post->ID, '_saas_bg_type', true);
        $bg_value = get_post_meta($post->ID, '_saas_bg_color', true);

        wp_nonce_field( 'saas_save_admin_profile', 'saas_admin_profile_nonce' );
        ?>
        <p>
            <label>Headline:</label><br>
            <input type="text" name="saas_headline" value="<?php echo esc_attr($headline); ?>" class="widefat">
        </p>
        <p>
            <label>Bio:</label><br>
            <textarea name="saas_bio" class="widefat"><?php echo esc_textarea($meta['bio']); ?></textarea>
        </p>
        <p>
            <label>Theme Color:</label><br>
            <input type="color" name="saas_theme_color" value="<?php echo esc_attr($meta['theme_color']); ?>">
        </p>
        <hr>
        <h4>Background Styling</h4>
        <p>
            <select name="saas_bg_type">
                <option value="flat" <?php selected($bg_type, 'flat'); ?>>Flat Color</option>
                <option value="gradient" <?php selected($bg_type, 'gradient'); ?>>Gradient</option>
            </select>
            <input type="text" name="saas_bg_value" value="<?php echo esc_attr($bg_value); ?>" placeholder="#ffffff or linear-gradient(...)">
        </p>
        <hr>
        <h4>License Details</h4>
        <p>
            <label>License Key:</label><br>
            <input type="text" name="saas_license_key" value="<?php echo esc_attr(get_post_meta($post->ID, '_saas_license_key', true)); ?>" class="widefat">
            <small>Assign a valid system license to this profile.</small>
        </p>
        <hr>
        <h4>Block & Font Styling</h4>
        <p>
            <label>Button Shape:</label>
            <select name="saas_btn_shape">
                <option value="pill" <?php selected(get_post_meta($post->ID, '_saas_btn_shape', true), 'pill'); ?>>Pill</option>
                <option value="rounded" <?php selected(get_post_meta($post->ID, '_saas_btn_shape', true), 'rounded'); ?>>Rounded</option>
                <option value="square" <?php selected(get_post_meta($post->ID, '_saas_btn_shape', true), 'square'); ?>>Square</option>
            </select>
        </p>
        <p>
            <label>Font Family:</label>
            <select name="saas_font_family">
                <option value="'Inter', sans-serif" <?php selected(get_post_meta($post->ID, '_saas_font_family', true), "'Inter', sans-serif"); ?>>Inter (Modern)</option>
                <option value="'Roboto', sans-serif" <?php selected(get_post_meta($post->ID, '_saas_font_family', true), "'Roboto', sans-serif"); ?>>Roboto (Clean)</option>
                <option value="serif" <?php selected(get_post_meta($post->ID, '_saas_font_family', true), "serif"); ?>>Serif (Elegant)</option>
            </select>
        </p>
        <?php
    }

    public function save_profile_meta( $post_id ) {
        if ( ! isset( $_POST['saas_admin_profile_nonce'] ) || ! wp_verify_nonce( $_POST['saas_admin_profile_nonce'], 'saas_save_admin_profile' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        update_post_meta( $post_id, '_saas_headline', sanitize_text_field( $_POST['saas_headline'] ) );
        update_post_meta( $post_id, '_saas_bio', sanitize_textarea_field( $_POST['saas_bio'] ) );
        update_post_meta( $post_id, '_saas_theme_color', sanitize_hex_color( $_POST['saas_theme_color'] ) );
        update_post_meta( $post_id, '_saas_bg_type', sanitize_text_field( $_POST['saas_bg_type'] ) );
        update_post_meta( $post_id, '_saas_bg_color', sanitize_text_field( $_POST['saas_bg_value'] ) );
        update_post_meta( $post_id, '_saas_btn_shape', sanitize_text_field( $_POST['saas_btn_shape'] ) );
        update_post_meta( $post_id, '_saas_font_family', sanitize_text_field( $_POST['saas_font_family'] ) );
        update_post_meta( $post_id, '_saas_license_key', sanitize_text_field( $_POST['saas_license_key'] ) );
    }

    public function add_links_column( $columns ) {
        $columns['links_count'] = 'Blocks/Links';
        return $columns;
    }

    public function render_links_column( $column, $post_id ) {
        if ( $column === 'links_count' ) {
            $author_id = get_post_field( 'post_author', $post_id );
            $links = get_posts([
                'post_type' => 'saas_link',
                'author'    => $author_id,
                'numberposts' => -1
            ]);
            echo count($links) . ' blocks. <a href="'.admin_url('edit.php?post_type=saas_link&author='.$author_id).'">Manage Blocks</a>';
        }
    }
}
new Saas_Admin_Profile_Manager();
