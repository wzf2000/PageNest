<?php
defined('ABSPATH') || exit();
function pagenest_home_topics(): void
{
    ?>
<section id="topics" class="pagenest-home-section" data-pagenest-home-module="topics">
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
            if (
                !is_array($topic) ||
                empty($topic['title']) ||
                empty($topic['url']) ||
                !esc_url($topic['url'])
            ) {
                continue;
            } ?>
        <a href="<?php echo esc_url(
            $topic['url'],
        ); ?>"><span class="pagenest-topic-number"><?php echo esc_html(
    sprintf('%02d', $k + 1),
); ?></span>
            <h3><?php echo esc_html($topic['title']); ?></h3><?php
if (!empty($topic['description'])): ?><p class="pagenest-topic-description"><?php echo esc_html(
    $topic['description'],
); ?></p><?php endif;
if (!empty($topic['status'])): ?><small class="pagenest-topic-status"><?php echo esc_html(
    $topic['status'],
); ?></small><?php endif;
?><span>浏览相关内容 →</span>
        </a>
        <?php
        endforeach;
        if (!$topics): ?><p>专题正在整理，先浏览最新文章吧。</p><?php endif;?>
    </div>
</section>
<?php
}
function pagenest_home_authors(): void
{
    ?>
<section id="authors" class="pagenest-home-section pagenest-about" data-pagenest-home-module="authors">
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
        endforeach;?>
    </div>
</section>
<?php
}
