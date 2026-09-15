<?php
if (!defined('ABSPATH')) {
    exit();
}
function pagenest_posts_url(): string
{
    return get_permalink((int) get_option('page_for_posts')) ?: home_url('/');
}
function pagenest_menu_branch(array $items, int $parent = 0, array $ancestors = []): void
{
    if (count($ancestors) > 8) {
        return;
    }
    foreach ($items as $item) {
        if (
            (int) $item->menu_item_parent !== $parent ||
            in_array((int) $item->ID, $ancestors, true)
        ) {
            continue;
        }
        if ($item->type === 'post_type' && $item->object_id) {
            $post = get_post($item->object_id);
            if (
                !$post ||
                ($post->post_status !== 'publish' && !current_user_can('read_post', $post->ID))
            ) {
                continue;
            }
        }
        $children = array_filter(
            $items,
            static fn($child) => (int) $child->menu_item_parent === (int) $item->ID,
        );
        if ($children) { ?>
<details class="pagenest-dropdown">
    <summary><?php echo esc_html($item->title); ?></summary>
    <div class="pagenest-dropdown-links">
        <?php pagenest_menu_branch($items, (int) $item->ID, [
            ...$ancestors,
            (int) $item->ID,
        ]); ?></div>
</details>
<?php } else { ?><a href="<?php echo esc_url($item->url); ?>"><?php echo esc_html(
    $item->title,
); ?></a><?php }
    }
}
function pagenest_card(int $id): void
{
    $post = get_post($id);
    if (
        !$post ||
        ($post->post_status !== 'publish' &&
            !($post->post_status === 'private' && current_user_can('read_post', $id)))
    ) {
        return;
    }
    $categories = get_the_category($id);
    ?>
<article class="pagenest-entry <?php echo has_post_thumbnail($id) ? '' : 'pagenest-no-image'; ?>">
    <?php if (has_post_thumbnail($id)): ?><a class="pagenest-entry-image" href="<?php echo esc_url(
    get_permalink($id),
); ?>" tabindex="-1" aria-hidden="true"><?php echo get_the_post_thumbnail($id, 'medium_large', [
    'loading' => 'lazy',
    'alt' => '',
]); ?></a><?php endif; ?>
    <div class="pagenest-entry-body">
        <div class="pagenest-entry-labels"><?php
        if (
            $post->post_status === 'private'
        ): ?><span class="pagenest-badge">私密</span><?php endif;
        if ($categories): ?><a href="<?php echo esc_url(
    get_category_link($categories[0]),
); ?>"><?php echo esc_html($categories[0]->name); ?></a><?php endif;
        ?></div>
        <h2><a href="<?php echo esc_url(
            get_permalink($id),
        ); ?>"><?php echo esc_html($post->post_title); ?></a></h2>
        <p class="pagenest-excerpt"><?php echo esc_html(
            post_password_required($post)
                ? '这篇文章需要密码才能阅读。'
                : mb_strimwidth(
                    preg_replace(
                        '/\s+/u',
                        ' ',
                        wp_strip_all_tags(
                            strip_shortcodes($post->post_excerpt ?: $post->post_content),
                        ),
                    ),
                    0,
                    170,
                    '…',
                    'UTF-8',
                ),
        ); ?></p>
        <div class="pagenest-author pagenest-author-compact"><?php echo get_avatar(
            $post->post_author,
            28,
            '',
            '',
            ['class' => 'pagenest-avatar'],
        ); ?><div><a class="pagenest-author-name"
                    href="<?php echo esc_url(
                        get_author_posts_url($post->post_author),
                    ); ?>"><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></a><time
                    datetime="<?php echo esc_attr(
                        get_the_date(DATE_W3C, $id),
                    ); ?>"><?php echo esc_html(get_the_date('Y年n月j日', $id)); ?></time>
            </div>
        </div>
    </div>
</article>
<?php
}

function pagenest_url($url)
{
    return $url;
}
function pagenest_author($id = null, $compact = false)
{
    $id = $id ?: get_the_ID();
    $author = (int) get_post_field('post_author', $id);
    ?><div class="pagenest-author"><?php echo get_avatar($author, 40); ?><div><a
            href="<?php echo esc_url(
                get_author_posts_url($author),
            ); ?>"><?php echo esc_html(get_the_author_meta('display_name', $author)); ?></a><time
            datetime="<?php echo esc_attr(
                get_the_date(DATE_W3C, $id),
            ); ?>"><?php echo esc_html(get_the_date('Y年n月j日', $id)); ?></time>
    </div>
</div><?php
}
function pagenest_search()
{
    get_search_form();
}
