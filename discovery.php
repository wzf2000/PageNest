<?php
/** Search presentation; the optional Companion supplies live results and additional scopes. */
defined('ABSPATH') || exit();
function pagenest_search_kinds(): array
{
    return apply_filters('pagenest_search_kinds', [
        'all' => __('All content', 'pagenest'),
        'post' => __('Articles', 'pagenest'),
        'page' => __('Pages', 'pagenest'),
    ]);
}
function pagenest_search_kind(): string
{
    $kind =
        isset($_GET['pn_kind']) && is_string($_GET['pn_kind'])
            ? sanitize_key(wp_unslash($_GET['pn_kind']))
            : 'all';
    return array_key_exists($kind, pagenest_search_kinds()) ? $kind : 'all';
}
function pagenest_discovery_links(): void
{
    $topics = get_theme_mod('pagenest_topics', []);
    if (!is_array($topics)) {
        return;
    }
    echo '<nav class="pagenest-discovery-links" aria-label="' .
        esc_attr__('Explore topics', 'pagenest') .
        '">';
    foreach (array_slice($topics, 0, 6) as $topic) {
        if (
            !is_array($topic) ||
            empty($topic['title']) ||
            empty($topic['url']) ||
            !esc_url($topic['url'])
        ) {
            continue;
        }
        echo '<a href="' . esc_url($topic['url']) . '">' . esc_html($topic['title']) . '</a>';
    }
    echo '</nav>';
}
// Native WordPress search remains usable without JavaScript or Companion.
add_action('pre_get_posts', static function ($query): void {
    if (
        is_admin() ||
        !$query->is_main_query() ||
        !$query->is_search() ||
        !isset($_GET['pn_kind'])
    ) {
        return;
    }
    $kind = pagenest_search_kind();
    $query->set('post_type', in_array($kind, ['post', 'page'], true) ? $kind : ['post', 'page']);
});
add_action('wp_enqueue_scripts', static function (): void {
    $manifest = require __DIR__ . '/assets/manifest.php';
    wp_enqueue_style(
        'pagenest-discovery',
        get_theme_file_uri('assets/' . $manifest['discovery_css']),
        ['pagenest'],
        null,
    );
    wp_enqueue_script(
        'pagenest-discovery',
        get_theme_file_uri('assets/' . $manifest['discovery_js']),
        [],
        null,
        true,
    );
});
