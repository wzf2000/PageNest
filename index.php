<?php
get_header();
$listing = pagenest_listing_presentation();
?>
<main id="pagenest-main" class="pagenest-main" tabindex="-1">
    <header class="pagenest-list-header">
        <p class="pagenest-eyebrow"><?php echo esc_html__('Write and share', 'pagenest'); ?></p>
        <h1><?php if (is_404()) {
            echo esc_html__('Page not found', 'pagenest');
        } elseif (is_home()) {
            echo esc_html__('Articles and notes', 'pagenest');
        } elseif (is_search()) {
            echo esc_html(sprintf(__('Search: %s', 'pagenest'), get_search_query()));
        } else {
            echo esc_html(wp_strip_all_tags(get_the_archive_title()));
        } ?></h1>
        <?php if (is_category() || is_tag()):
            $description = get_the_archive_description();
            if ($description): ?><div class="pagenest-archive-description"><?php echo wp_kses_post(
    $description,
); ?></div><?php endif;
        endif; ?>
        <?php if (is_category()):
            $feed = get_category_feed_link(get_queried_object_id());
            if ($feed): ?><p class="pagenest-category-feed"><a href="<?php echo esc_url(
    $feed,
); ?>"><?php echo esc_html__(
    'Subscribe to this category via RSS',
    'pagenest',
); ?></a></p><?php endif;
        endif; ?>
    </header>
    <?php if (
        is_search() ||
        is_404()
    ): ?><section class="pagenest-search-page" aria-label="<?php echo esc_attr__(
    'Find content',
    'pagenest',
); ?>"><?php get_search_form(); ?></section><?php endif; ?>
    <div class="pagenest-list-grid<?php echo $listing['sidebar']
        ? ''
        : ' pagenest-list-no-sidebar'; ?>">
        <div>
            <div class="pagenest-post-list<?php echo in_array(
                $listing['layout'],
                ['grid-2', 'grid-3'],
                true,
            )
                ? ' pagenest-list-' . esc_attr($listing['layout'])
                : ''; ?>"><?php if (have_posts() && !is_404()):
    while (have_posts()):
        the_post();
        pagenest_card(get_the_ID());
    endwhile;
else:
     ?><section class="pagenest-empty">
                    <h2><?php echo esc_html__('No content found', 'pagenest'); ?></h2>
                    <p><?php echo esc_html__(
                        'Try another keyword or return to the home page to keep browsing.',
                        'pagenest',
                    ); ?></p><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html__(
    'Back to home',
    'pagenest',
); ?></a>
                </section><?php
endif; ?></div>
            <nav class="pagenest-pagination" aria-label="<?php echo esc_attr__(
                'Article list pagination',
                'pagenest',
            ); ?>"><?php echo paginate_links([
    'prev_text' =>
        '<span class="pagenest-direction-icon" aria-hidden="true">←</span> ' .
        __('Previous page', 'pagenest'),
    'next_text' =>
        __('Next page', 'pagenest') .
        ' <span class="pagenest-direction-icon" aria-hidden="true">→</span>',
]); ?></nav>
        </div><?php if ($listing['sidebar']) {
            pagenest_side();
        } ?>
    </div>
</main>
<?php get_footer(); ?>
