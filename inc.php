<?php
if (!defined('ABSPATH')) {
    exit();
}
function pagenest_posts_url(): string
{
    $page = (int) get_option('page_for_posts');
    return $page > 0 ? (get_permalink($page) ?: home_url('/')) : home_url('/');
}
/** Defaults contain no site-specific content or fixed page IDs. */
function pagenest_setting_defaults(): array
{
    return [
        'pagenest_home_eyebrow' => __('Write · Share · Explore', 'pagenest'),
        'pagenest_home_intro' => __(
            "Record ideas and discoveries.\nOrganize your thoughts and share your explorations here.",
            'pagenest',
        ),
        'pagenest_authors_intro' => __(
            "Different interests, shared stories.\nMeet the authors sharing their experiences here.",
            'pagenest',
        ),
        'pagenest_footer_tagline' => __('A home for knowledge and ideas', 'pagenest'),
        'pagenest_community_description' => __(
            'Share your thoughts and suggestions in the article comments.',
            'pagenest',
        ),
        'pagenest_community_link_label' => __('More content', 'pagenest'),
        'pagenest_community_link_url' => '',
        'pagenest_archive_link_label' => __('Archive', 'pagenest'),
        'pagenest_community_archive_label' => __('Full archive →', 'pagenest'),
        'pagenest_archive_link_url' => pagenest_posts_url(),
        'pagenest_about_link_label' => __('About', 'pagenest'),
        'pagenest_about_link_url' => home_url('/') . '#authors',
    ];
}
function pagenest_setting(string $key): string
{
    $defaults = pagenest_setting_defaults();
    $value = get_theme_mod($key, $defaults[$key] ?? '');
    return is_scalar($value) ? (string) $value : '';
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
function pagenest_card(int $id, string $context = 'list'): void
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
    $cover =
        has_post_thumbnail($id) &&
        !post_password_required($post) &&
        ($context === 'home' ||
            pagenest_browsing_bool(get_theme_mod('pagenest_list_covers', true)));
    $compact =
        $context === 'list' &&
        pagenest_list_style(get_theme_mod('pagenest_list_style', 'standard')) === 'compact';
    ?>
<article class="pagenest-entry <?php echo ($cover ? '' : 'pagenest-no-image') .
    ($compact ? ' pagenest-entry-compact' : ''); ?>">
    <?php if ($cover): ?><a class="pagenest-entry-image" href="<?php echo esc_url(
    get_permalink($id),
); ?>" tabindex="-1" aria-hidden="true"><?php echo get_the_post_thumbnail($id, 'medium_large', [
    'loading' => 'lazy',
    'alt' => '',
]); ?></a><?php endif; ?>
    <div class="pagenest-entry-body">
        <div class="pagenest-entry-labels"><?php
        if ($post->post_status === 'private'): ?><span class="pagenest-badge"><?php echo esc_html__(
    'Private',
    'pagenest',
); ?></span><?php endif;
        if ($categories): ?><a href="<?php echo esc_url(
    get_category_link($categories[0]),
); ?>"><?php echo esc_html($categories[0]->name); ?></a><?php endif;
        ?></div>
        <h2><a href="<?php echo esc_url(
            get_permalink($id),
        ); ?>"><?php echo is_search() ? pagenest_search_highlight($post->post_title) : esc_html($post->post_title); ?></a></h2>
        <?php
        if (is_search() && !post_password_required($post)): ?>
        <p class="pagenest-excerpt"><?php echo pagenest_search_highlight(
            pagenest_search_result_details($post),
        ); ?></p>
        <?php
        $sections = apply_filters('pagenest_search_sections', [], $post, get_search_query(false));
        if ($sections): ?><ul class="pagenest-search-sections"><?php foreach (
    array_slice($sections, 0, 3)
    as $section
): ?><li><a href="<?php echo esc_url($section['url']); ?>"><?php echo pagenest_search_highlight(
    $section['title'],
); ?></a></li><?php endforeach; ?></ul><?php endif;
        ?>
        <?php else: ?><p class="pagenest-excerpt"><?php echo esc_html(
    post_password_required($post)
        ? __('This article requires a password.', 'pagenest')
        : mb_strimwidth(
            preg_replace(
                '/\s+/u',
                ' ',
                wp_strip_all_tags(strip_shortcodes($post->post_excerpt ?: $post->post_content)),
            ),
            0,
            170,
            '…',
            'UTF-8',
        ),
); ?></p>
        <?php endif;
        pagenest_browsing_metadata($id);
        ?>
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
                    ); ?>"><?php echo esc_html(get_the_date(__('F j, Y', 'pagenest'), $id)); ?></time>
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
            ); ?>"><?php echo esc_html(get_the_date(__('F j, Y', 'pagenest'), $id)); ?></time>
    </div>
</div><?php
}
function pagenest_search()
{
    get_search_form();
}
