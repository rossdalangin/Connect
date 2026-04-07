<?php
/**
 * Template Name: Legal Page (Privacy/Terms)
 */

get_header(); ?>

<main id="legal-page" class="site-main site-container" style="max-width: 800px; margin: 80px auto; padding: 0 20px; line-height: 1.8; color: #444;">
    <div style="background:#fff; padding:60px; border-radius:32px; box-shadow:0 15px 40px rgba(0,0,0,0.05);">
        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <header class="entry-header" style="margin-bottom: 40px; text-align: center;">
                <?php the_title( '<h1 class="entry-title" style="font-size: 3rem; font-weight: 900;">', '</h1>' ); ?>
                <p style="color: #999;">Last Updated: <?php echo get_the_modified_date(); ?></p>
            </header>

            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            <?php
        endwhile;
        ?>
    </div>
</main>

<style>
    #legal-page h2 { margin-top: 40px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
    #legal-page p { margin-bottom: 20px; }
</style>

<?php get_footer(); ?>
