<?php
/**
 * Template Name: Clean Layout (Distraction-Free)
 */

get_header(); ?>

<main id="clean-layout" class="site-main" style="max-width: 700px; margin: 100px auto; padding: 0 40px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
    <?php
    while ( have_posts() ) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> style="padding: 60px 0;">
            <header class="clean-header" style="text-align: center; margin-bottom: 40px;">
                <?php the_title( '<h1 class="clean-title" style="font-weight:800; font-size:2.5rem;">', '</h1>' ); ?>
            </header>

            <div class="clean-content" style="line-height: 1.8; font-size: 1.15rem; color: #444;">
                <?php the_content(); ?>
            </div>
        </article>
        <?php
    endwhile;
    ?>
</main>

<style>
    body { background-color: #f9f9f9; }
    .clean-content img { max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 24px; }
</style>

<?php get_footer(); ?>
