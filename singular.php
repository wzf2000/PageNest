<?php get_header(); ?>
<main id="wzfl-main" class="wzfl-main" tabindex="-1">
    <?php while (have_posts()):

        the_post();
        $article = is_singular('post');
        ?>
    <div class="<?php echo $article ? 'wzfl-reading-grid' : 'wzfl-page-column'; ?>">
        <div class="wzfl-article-column">
            <article id="post-<?php the_ID(); ?>" <?php post_class(
    'wzfl-article' . ($article ? '' : ' wzfl-page'),
); ?>>
                <header class="wzfl-article-header">
                    <div class="wzfl-entry-labels"><a href="<?php echo esc_url(
                        wzfj_posts_url(),
                    ); ?>">文章与笔记</a><?php if (
    get_post_status() === 'private'
): ?><span class="wzfl-badge">私密</span><?php endif; ?></div>
                    <h1><?php echo esc_html(
                        get_post_field('post_title', get_the_ID()),
                    ); ?></h1><?php if ($article) {
    wzfj_author();
} ?>
                </header>
                <?php if (
                    has_post_thumbnail() &&
                    !post_password_required()
                ): ?><figure class="wzfl-cover"><?php the_post_thumbnail('large', [
    'loading' => 'eager',
]); ?></figure><?php endif; ?>
                <div class="wzfl-article-body"><?php
                if (
                    !post_password_required() &&
                    get_page_template_slug() === 'pages/page-archives.php'
                ) {
                    wzfj_archives();
                } else {
                    the_content();
                }
                wp_link_pages([
                    'before' => '<nav class="wzfl-pagination" aria-label="正文分页">',
                    'after' => '</nav>',
                ]);
                ?></div>
                <?php if (
                    $article &&
                    !post_password_required()
                ): ?><footer class="wzfl-article-footer"><a href="#comments"><?php echo esc_html(
    get_comments_number(),
); ?> 条评论</a><?php
 if (function_exists('the_views')) {
     echo '<span>';
     the_views();
     echo '</span>';
 }
 if (
     class_exists('WZF_Experience') &&
     WZF_Experience::ready() &&
     get_post_status() === 'publish'
 ): ?><button type="button" class="favorite" data-id="<?php the_ID(); ?>">点赞 <span class="count"><?php echo absint(
    get_post_meta(get_the_ID(), 'bigfa_ding', true),
); ?></span></button><?php endif;
 edit_post_link('编辑文章');
 ?></footer><?php endif; ?>
            </article>
            <?php if (
                $article &&
                !post_password_required()
            ): ?><nav class="wzfl-adjacent" aria-label="相邻文章"><?php foreach (
    [true => '上一篇', false => '下一篇']
    as $previous => $label
) {
    $adjacent = get_adjacent_post(false, '', (bool) $previous);
    if (
        $adjacent &&
        ($adjacent->post_status === 'publish' || current_user_can('read_post', $adjacent->ID))
    ): ?><a href="<?php echo esc_url(get_permalink($adjacent)); ?>"><small><?php echo esc_html(
    $label,
); ?></small><span><?php echo esc_html($adjacent->post_title); ?></span></a><?php endif;
} ?></nav><?php endif; ?>
            <?php if (
                comments_open() ||
                get_comments_number()
            ): ?><div id="comments" class="wzfl-comments"><?php comments_template(); ?></div>
            <?php endif; ?>
        </div><?php if ($article) {
            wzfj_side(true);
        } ?>
    </div>
    <?php
    endwhile; ?>
</main>
<?php get_footer(); ?>
