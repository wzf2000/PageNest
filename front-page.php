<?php get_header(); ?>
<main id="pagenest-main" class="pagenest-main" tabindex="-1">
    <section class="pagenest-hero">
        <div class="pagenest-hero-copy">
            <?php if ($eyebrow = pagenest_setting('pagenest_home_eyebrow')): ?>
            <p class="pagenest-eyebrow"><?php echo esc_html($eyebrow); ?></p>
            <?php endif; ?>
            <h1><?php bloginfo('name'); ?></h1>
            <?php if ($intro = pagenest_setting('pagenest_home_intro')): ?>
            <p><?php echo nl2br(esc_html($intro)); ?></p>
            <?php endif; ?>
            <div class="pagenest-hero-actions"><a class="pagenest-button" href="<?php echo esc_url(
                pagenest_posts_url(),
            ); ?>"><?php echo esc_html__(
    'Browse articles',
    'pagenest',
); ?> <span aria-hidden="true">→</span></a><?php if (
     in_array('topics', pagenest_home_order(), true)
 ): ?><a href="#topics"><?php echo esc_html__(
    'Explore topics',
    'pagenest',
); ?></a><?php endif; ?></div>
        </div>
    </section>
    <?php foreach (pagenest_home_order() as $module) {
        if ($module === 'topics') {
            pagenest_home_topics();
        } elseif ($module === 'authors') {
            pagenest_home_authors();
        } else {
            pagenest_home_post_section($module);
        }
    } ?>
</main>
<?php get_footer(); ?>
