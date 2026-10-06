<?php
if (!defined('ABSPATH')) {
    exit();
}
/** Extensions may provide post objects or IDs; visibility is checked after filtering. */
function pagenest_related($id)
{
    $id = absint($id);
    $cats = wp_get_post_categories($id);
    $related = $cats
        ? get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'post__not_in' => [$id],
            'category__in' => $cats,
            'ignore_sticky_posts' => true,
        ])
        : [];
    $related = apply_filters('pagenest_related_posts', $related, $id);
    $out = [];
    foreach (is_array($related) ? $related : [] as $candidate) {
        $post_id = $candidate instanceof WP_Post ? $candidate->ID : $candidate;
        if (!is_numeric($post_id)) {
            continue;
        }
        $post_id = absint($post_id);
        $post = get_post($post_id);
        if (
            !$post ||
            $post_id === $id ||
            isset($out[$post_id]) ||
            $post->post_type !== 'post' ||
            post_password_required($post) ||
            ($post->post_status !== 'publish' &&
                !($post->post_status === 'private' && current_user_can('read_post', $post_id)))
        ) {
            continue;
        }
        $out[$post_id] = $post;
        if (count($out) === 3) {
            break;
        }
    }
    return array_values($out);
}
function pagenest_side($single = false)
{
    ?><aside class="pagenest-sidebar" aria-label="<?php echo $single
    ? '阅读导航'
    : '文章导航'; ?>"><?php if ($single): ?><div class="pagenest-reading-rail">
        <div class="pagenest-notes-slot" hidden></div>
        <details class="pagenest-toc" hidden open>
            <summary>本文目录</summary>
            <nav aria-label="本文目录"></nav>
        </details><?php
        $id = get_queried_object_id();
        $related = pagenest_related($id);
        if ($related) { ?><section class="pagenest-widget">
            <h2><?php echo esc_html(
                apply_filters('pagenest_related_title', '相关阅读', $id, $related),
            ); ?></h2>
            <ul><?php foreach ($related as $p): ?><li><a href="<?php echo esc_url(
    pagenest_url(get_permalink($p)),
); ?>"><?php echo esc_html($p->post_title); ?></a></li><?php endforeach; ?></ul>
        </section><?php }
        ?>
        <?php else: ?><section class="pagenest-widget"><?php pagenest_search(); ?></section>
        <section class="pagenest-widget">
            <h2>浏览分类</h2>
            <div class="pagenest-categories"><?php foreach (
                get_categories([
                    'number' => 8,
                    'orderby' => 'count',
                    'order' => 'DESC',
                    'hide_empty' => true,
                ])
                as $cat
            ): ?><a href="<?php echo esc_url(
    pagenest_url(get_category_link($cat)),
); ?>"><?php echo esc_html($cat->name); ?></a><?php endforeach; ?></div>
        </section>
        <section class="pagenest-widget">
            <h2>热门文章</h2>
            <p class="pagenest-hint">按累计浏览量</p>
            <ol class="pagenest-popular"><?php
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
    pagenest_url(get_permalink($p)),
); ?>"><?php echo esc_html($p->post_title); ?></a><span><?php echo esc_html(
    number_format_i18n((int) get_post_meta($p->ID, 'views', true)),
); ?> 次浏览</span></li><?php endforeach;
            ?></ol>
        </section><?php endif; ?><section class="pagenest-widget pagenest-community">
            <h2>交流与更多</h2>
            <?php if ($description = pagenest_setting('pagenest_community_description')): ?>
            <p><?php echo nl2br(esc_html($description)); ?></p>
            <?php endif; ?>
            <?php foreach (['community', 'archive'] as $link):
                $label = pagenest_setting(
                    $link === 'archive'
                        ? 'pagenest_community_archive_label'
                        : 'pagenest_community_link_label',
                );
                $url = pagenest_setting('pagenest_' . $link . '_link_url');
                if ($label !== '' && esc_url($url) !== ''): ?>
            <p><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a></p>
            <?php endif;
            endforeach; ?>
        </section><?php if ($single): ?>
    </div><?php endif; ?>
</aside><?php
}
function pagenest_archives()
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
        ' 篇公开文章，按发布时间排列。</p><div class="pagenest-archive-tags">';
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
            echo '<section class="pagenest-archive-month"><h2>' . esc_html($m) . '</h2><ul>';
            $month = $m;
        }
        echo '<li><time datetime="' .
            esc_attr(get_the_date('Y-m-d', $p)) .
            '">' .
            esc_html(get_the_date('m/d', $p)) .
            '</time><a href="' .
            esc_url(pagenest_url(get_permalink($p))) .
            '">' .
            esc_html($p->post_title) .
            '</a></li>';
    }
    if ($month) {
        echo '</ul></section>';
    }
}
function pagenest_comment($comment, $args, $depth)
{
    $GLOBALS['comment'] = $comment;
    $name = get_comment_author($comment);
    $is_author =
        (int) $comment->user_id > 0 &&
        (int) $comment->user_id === (int) get_post_field('post_author', $comment->comment_post_ID);
    ?>
<li <?php comment_class(
    'pagenest-discussion-item',
    $comment,
); ?> id="comment-<?php comment_ID(); ?>">
    <article id="div-comment-<?php comment_ID(); ?>" class="pagenest-comment-card">
        <header class="pagenest-comment-heading"><span class="pagenest-comment-avatar"
                aria-hidden="true"><span><?php echo esc_html(
                    mb_substr($name, 0, 1),
                ); ?></span><?php echo get_avatar($comment, 36, '', '', ['loading' => 'lazy']); ?></span>
            <div class="pagenest-comment-identity">
                <div class="pagenest-comment-name"><?php
                echo get_comment_author_link($comment);
                if (
                    $is_author
                ): ?><span class="pagenest-comment-author-badge">作者</span><?php endif;
                ?></div><a class="pagenest-comment-time"
                    href="<?php echo esc_url(get_comment_link($comment)); ?>"><time
                        datetime="<?php echo esc_attr(
                            get_comment_date(DATE_W3C, $comment),
                        ); ?>"><?php echo esc_html(get_comment_date('Y年n月j日', $comment) . ' ' . get_comment_time('H:i')); ?></time></a>
            </div>
        </header>
        <?php if (
            $comment->comment_parent &&
            ($parent = get_comment($comment->comment_parent))
        ): ?><p class="pagenest-comment-context">回复 <?php echo esc_html(
    get_comment_author($parent),
); ?></p><?php endif; ?>
        <?php if (
            '0' === $comment->comment_approved
        ): ?><p class="pagenest-comment-pending">你的评论正在等待审核。</p><?php endif; ?>
        <div class="pagenest-comment-text"><?php comment_text($comment); ?></div>
        <div class="pagenest-comment-actions"><?php
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
