<?php
$slug = get_query_var( 'saas_profile' );
if ($slug) {
    $profile = saas_get_profile_by_slug($slug);
    if ($profile) {
        $payments = new Saas_Payments();
        if ($payments->is_pro_user($profile->post_author)) {
            echo get_post_meta($profile->ID, '_saas_footer_scripts', true);
        }
    }
}
?>
<?php wp_footer(); ?>
</body>
</html>
