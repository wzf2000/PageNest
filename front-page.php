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
            ); ?>">浏览文章 <span aria-hidden="true">→</span></a><a href="#topics">探索专题</a></div>
        </div>
    </section>
    <section id="topics" class="pagenest-home-section">
        <div class="pagenest-section-heading">
            <div>
                <p class="pagenest-eyebrow">探索专题</p>
                <h2>从感兴趣的专题开始</h2>
            </div>
        </div>
        <div class="pagenest-topic-grid">
            <?php
            $topics = get_theme_mod('pagenest_topics', []);
            foreach (is_array($topics) ? $topics : [] as $k => $topic):
                if (empty($topic['title']) || empty($topic['url'])) {
                    continue;
                } ?>
            <a href="<?php echo esc_url(
                $topic['url'],
            ); ?>"><span class="pagenest-topic-number"><?php echo esc_html(
    sprintf('%02d', $k + 1),
); ?></span>
                <h3><?php echo esc_html($topic['title']); ?></h3><span>浏览相关内容 →</span>
            </a>
            <?php
            endforeach;
            if (!$topics): ?><p>专题正在整理，先浏览最新文章吧。</p><?php endif;
            ?>
        </div>
    </section>
    <section class="pagenest-home-section">
        <div class="pagenest-section-heading">
            <div>
                <p class="pagenest-eyebrow">最新记录</p>
                <h2>最新文章</h2>
            </div><a href="<?php echo esc_url(pagenest_posts_url()); ?>">全部文章 →</a>
        </div>
        <div class="pagenest-home-posts">
            <?php
            $latest = new WP_Query([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 4,
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
            ]);
            foreach ($latest->posts as $entry) {
                pagenest_card($entry->ID);
            }
            if (!$latest->posts): ?><p>还没有公开文章。</p><?php endif;
            ?>
        </div>
    </section>
    <section id="authors" class="pagenest-home-section pagenest-about">
        <div>
            <p class="pagenest-eyebrow">共同记录</p>
            <h2>作者们</h2>
            <?php if ($intro = pagenest_setting('pagenest_authors_intro')): ?>
            <p><?php echo nl2br(esc_html($intro)); ?></p>
            <?php endif; ?>
        </div>
        <div class="pagenest-authors">
            <?php
            $authors = get_theme_mod('pagenest_authors', []);
            foreach (is_array($authors) ? $authors : [] as $author):
                if (empty(trim($author['name'] ?? ''))) {
                    continue;
                } ?><div class="pagenest-author-card"><?php if (!empty($author['attachment_id'])) {
    echo wp_get_attachment_image((int) $author['attachment_id'], [56, 56], false, [
        'alt' => '',
        'loading' => 'lazy',
    ]);
} ?><span><?php echo esc_html($author['name'] ?? ''); ?></span></div><?php
            endforeach;
            ?>
        </div>
    </section>
</main>
<?php get_footer(); ?>
