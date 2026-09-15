<?php get_header(); ?>
<main id="pagenest-main" class="pagenest-main" tabindex="-1">
    <header class="pagenest-list-header">
        <p class="pagenest-eyebrow">记录与分享</p>
        <h1><?php if (is_404()) {
            echo '没有找到这个页面';
        } elseif (is_home()) {
            echo '文章与笔记';
        } elseif (is_search()) {
            echo '搜索：' . esc_html(get_search_query());
        } else {
            echo esc_html(wp_strip_all_tags(get_the_archive_title()));
        } ?></h1>
    </header>
    <div class="pagenest-list-grid">
        <div>
            <div class="pagenest-post-list"><?php if (have_posts() && !is_404()):
                while (have_posts()):
                    the_post();
                    pagenest_card(get_the_ID());
                endwhile;
            else:
                 ?><section class="pagenest-empty">
                    <h2>暂时没有找到内容</h2>
                    <p>试试其他关键词，或者返回首页继续浏览。</p><a href="<?php echo esc_url(
                        home_url('/'),
                    ); ?>">返回首页</a>
                </section><?php
            endif; ?></div>
            <nav class="pagenest-pagination" aria-label="文章列表分页"><?php echo paginate_links([
                'prev_text' => '← 上一页',
                'next_text' => '下一页 →',
            ]); ?></nav>
        </div><?php pagenest_side(); ?>
    </div>
</main>
<?php get_footer(); ?>
