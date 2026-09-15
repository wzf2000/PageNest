<?php get_header(); ?>
<main id="wzfl-main" class="wzfl-main" tabindex="-1">
    <section class="wzfl-hero">
        <div class="wzfl-hero-copy">
            <p class="wzfl-eyebrow">技术 · 笔记 · 生活</p>
            <h1><?php bloginfo('name'); ?></h1>
            <p>记录技术、学习与生活。<br>在这里整理思路，也分享探索的过程。</p>
            <div class="wzfl-hero-actions"><a class="wzfl-button" href="<?php echo esc_url(
                wzfj_posts_url(),
            ); ?>">浏览文章 <span aria-hidden="true">→</span></a><a href="#topics">探索专题</a></div>
        </div>
    </section>
    <section id="topics" class="wzfl-home-section">
        <div class="wzfl-section-heading">
            <div>
                <p class="wzfl-eyebrow">探索专题</p>
                <h2>从感兴趣的专题开始</h2>
            </div>
        </div>
        <div class="wzfl-topic-grid">
            <?php
            $topics = get_theme_mod('wzfj_topics', []);
            foreach (is_array($topics) ? $topics : [] as $k => $topic):
                if (empty($topic['title']) || empty($topic['url'])) {
                    continue;
                } ?>
            <a href="<?php echo esc_url(
                $topic['url'],
            ); ?>"><span class="wzfl-topic-number"><?php echo esc_html(
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
    <section class="wzfl-home-section">
        <div class="wzfl-section-heading">
            <div>
                <p class="wzfl-eyebrow">最新记录</p>
                <h2>最新文章</h2>
            </div><a href="<?php echo esc_url(wzfj_posts_url()); ?>">全部文章 →</a>
        </div>
        <div class="wzfl-home-posts">
            <?php
            $latest = new WP_Query([
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 4,
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
            ]);
            foreach ($latest->posts as $entry) {
                wzfj_card($entry->ID);
            }
            if (!$latest->posts): ?><p>还没有公开文章。</p><?php endif;
            ?>
        </div>
    </section>
    <section id="authors" class="wzfl-home-section wzfl-about">
        <div>
            <p class="wzfl-eyebrow">共同记录</p>
            <h2>作者们</h2>
            <p>不同的兴趣，共同的记录。<br>这里汇集了大家的学习笔记、技术实践与生活片段。</p>
        </div>
        <div class="wzfl-authors">
            <?php
            $authors = get_theme_mod('wzfj_authors', []);
            foreach (is_array($authors) ? $authors : [] as $author):
                if (empty(trim($author['name'] ?? ''))) {
                    continue;
                } ?><div class="wzfl-author-card"><?php if (!empty($author['attachment_id'])) {
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
