<?php
if (!defined('ABSPATH')) {
    exit();
}
function wzfj_related($id)
{
    if (get_post_meta($id, '_llm_document_id', true)) {
        $all = get_posts([
            'post_type' => 'post',
            'post_status' => ['publish', 'private'],
            'posts_per_page' => 100,
            'meta_key' => '_llm_document_id',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ]);
        $all = array_values(
            array_filter(
                $all,
                fn($p) => !post_password_required($p) &&
                    ($p->post_status === 'publish' || current_user_can('read_post', $p->ID)),
            ),
        );
        $index = array_search($id, array_column($all, 'ID'), true);
        if ($index === false) {
            return [];
        }
        $out = [];
        foreach ([1, -1, 2, -2] as $offset) {
            if (isset($all[$index + $offset])) {
                $out[] = $all[$index + $offset];
            }
        }
        return array_slice($out, 0, 3);
    }
    $cats = wp_get_post_categories($id);
    if (!$cats) {
        return [];
    }
    return get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 3,
        'post__not_in' => [$id],
        'category__in' => $cats,
        'ignore_sticky_posts' => true,
    ]);
}
function wzfj_side($single = false)
{
    ?><aside class="wzfl-sidebar" aria-label="<?php echo $single
    ? '阅读导航'
    : '文章导航'; ?>"><?php if ($single): ?><details class="wzfl-toc" hidden open>
        <summary>本文目录</summary>
        <nav aria-label="本文目录"></nav>
    </details><?php
    $id = get_queried_object_id();
    $related = wzfj_related($id);
    if ($related) { ?><section class="wzfl-widget">
        <h2><?php echo get_post_meta($id, '_llm_document_id', true)
            ? '同系列章节'
            : '相关阅读'; ?></h2>
        <ul><?php foreach ($related as $p): ?><li><a href="<?php echo esc_url(
    wzfj_url(get_permalink($p)),
); ?>"><?php echo esc_html($p->post_title); ?></a></li><?php endforeach; ?></ul>
    </section><?php }
    ?>
    <?php else: ?><section class="wzfl-widget"><?php wzfj_search(); ?></section>
    <section class="wzfl-widget">
        <h2>浏览分类</h2>
        <div class="wzfl-categories"><?php foreach (
            get_categories([
                'number' => 8,
                'orderby' => 'count',
                'order' => 'DESC',
                'hide_empty' => true,
            ])
            as $cat
        ): ?><a href="<?php echo esc_url(
    wzfj_url(get_category_link($cat)),
); ?>"><?php echo esc_html($cat->name); ?></a><?php endforeach; ?></div>
    </section>
    <section class="wzfl-widget">
        <h2>热门文章</h2>
        <p class="wzfl-hint">按累计浏览量</p>
        <ol class="wzfl-popular"><?php
        $popular = new WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'meta_key' => 'views',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ]);
        foreach ($popular->posts as $p): ?><li><a href="<?php echo esc_url(
    wzfj_url(get_permalink($p)),
); ?>"><?php echo esc_html($p->post_title); ?></a><span><?php echo esc_html(
    number_format_i18n((int) get_post_meta($p->ID, 'views', true)),
); ?> 次浏览</span></li><?php endforeach;
        ?></ol>
    </section><?php endif; ?><section class="wzfl-widget wzfl-community">
        <h2>交流与更多</h2>
        <p>欢迎在文章评论区交流想法与建议。</p>
        <p><a href="<?php echo esc_url(wzfj_url(get_permalink(801))); ?>">网站更新与想法 →</a></p><a
            href="<?php echo esc_url(wzfj_url(home_url('/archives/'))); ?>">完整归档 →</a>
    </section>
</aside><?php
}
function wzfj_archives()
{
    $posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'suppress_filters' => false,
    ]);
    echo '<p>共 ' .
        esc_html(count($posts)) .
        ' 篇公开文章，按发布时间排列。</p><div class="wzfl-archive-tags">';
    wp_tag_cloud([
        'number' => 18,
        'orderby' => 'count',
        'order' => 'DESC',
        'smallest' => 13,
        'largest' => 13,
        'unit' => 'px',
    ]);
    echo '</div>';
    $month = '';
    foreach ($posts as $p) {
        $m = get_the_date('Y年n月', $p);
        if ($m !== $month) {
            if ($month) {
                echo '</ul></section>';
            }
            echo '<section class="wzfl-archive-month"><h2>' . esc_html($m) . '</h2><ul>';
            $month = $m;
        }
        echo '<li><time datetime="' .
            esc_attr(get_the_date('Y-m-d', $p)) .
            '">' .
            esc_html(get_the_date('m/d', $p)) .
            '</time><a href="' .
            esc_url(wzfj_url(get_permalink($p))) .
            '">' .
            esc_html($p->post_title) .
            '</a></li>';
    }
    if ($month) {
        echo '</ul></section>';
    }
}
function wzfj_comment($comment, $args, $depth)
{
    $GLOBALS['comment'] = $comment;
    $name = get_comment_author($comment);
    $is_author =
        (int) $comment->user_id > 0 &&
        (int) $comment->user_id === (int) get_post_field('post_author', $comment->comment_post_ID);
    ?>
<li <?php comment_class('wzfl-discussion-item', $comment); ?> id="comment-<?php comment_ID(); ?>">
    <article id="div-comment-<?php comment_ID(); ?>" class="wzfl-comment-card">
        <header class="wzfl-comment-heading"><span class="wzfl-comment-avatar"
                aria-hidden="true"><span><?php echo esc_html(
                    mb_substr($name, 0, 1),
                ); ?></span><?php echo get_avatar($comment, 36, '', '', ['loading' => 'lazy']); ?></span>
            <div class="wzfl-comment-identity">
                <div class="wzfl-comment-name"><?php
                echo get_comment_author_link($comment);
                if ($is_author): ?><span class="wzfl-comment-author-badge">作者</span><?php endif;
                ?></div><a class="wzfl-comment-time"
                    href="<?php echo esc_url(get_comment_link($comment)); ?>"><time
                        datetime="<?php echo esc_attr(
                            get_comment_date(DATE_W3C, $comment),
                        ); ?>"><?php echo esc_html(get_comment_date('Y年n月j日', $comment) . ' ' . get_comment_time('H:i')); ?></time></a>
            </div>
        </header>
        <?php if (
            $comment->comment_parent &&
            ($parent = get_comment($comment->comment_parent))
        ): ?><p class="wzfl-comment-context">回复 <?php echo esc_html(
    get_comment_author($parent),
); ?></p><?php endif; ?>
        <?php if (
            '0' === $comment->comment_approved
        ): ?><p class="wzfl-comment-pending">你的评论正在等待审核。</p><?php endif; ?>
        <div class="wzfl-comment-text"><?php comment_text($comment); ?></div>
        <div class="wzfl-comment-actions"><?php
        comment_reply_link(
            array_merge($args, [
                'add_below' => 'div-comment',
                'depth' => $depth,
                'max_depth' => $args['max_depth'],
                'reply_text' => '回复',
                'login_text' => '登录后回复',
            ]),
            $comment,
        );
        edit_comment_link('编辑', '<span>', '</span>');?></div>
    </article>
    <?php
}
