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
    if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
        return;
    }
    $kind = pagenest_search_kind();
    $query->set('post_status', 'publish');
    $query->set('has_password', false);
    $query->set(
        'orderby',
        pagenest_search_param('pn_sort') === 'modified'
            ? ['modified' => 'DESC', 'ID' => 'DESC']
            : 'relevance',
    );
    if (absint(pagenest_search_param('pn_category'))) {
        $query->set('category__in', [absint(pagenest_search_param('pn_category'))]);
    }
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

function pagenest_search_param(string $key): string
{
    return isset($_GET[$key]) && is_string($_GET[$key])
        ? mb_substr(sanitize_text_field(wp_unslash($_GET[$key])), 0, 100)
        : '';
}
/** Escaping happens before adding the one supported highlight element. */
function pagenest_search_highlight(string $text): string
{
    $query = get_search_query(false);
    if ($query === '') {
        return esc_html($text);
    }
    $parts = preg_split(
        '/(' . preg_quote($query, '/') . ')/iu',
        $text,
        -1,
        PREG_SPLIT_DELIM_CAPTURE,
    );
    if ($parts === false) {
        return esc_html($text);
    }
    $out = '';
    foreach ($parts as $i => $part) {
        $out .= $i % 2 ? '<mark>' . esc_html($part) . '</mark>' : esc_html($part);
    }
    return $out;
}
function pagenest_search_result_details($post): string
{
    $query = get_search_query(false);
    $text = '';
    foreach ([$post->post_excerpt ?? '', $post->post_content] as $saved) {
        // Inspect saved text only. Shortcode output can contain private or dynamic data.
        if (
            preg_match(
                '/\[\/?[a-zA-Z][^\]]*\]/',
                preg_replace('/\[(?:[a-zA-Z][0-9]*\s*,\s*)+[a-zA-Z][0-9]*\s*\]/', '', $saved),
            )
        ) {
            continue;
        }
        $saved = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $saved);
        $text = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                html_entity_decode(wp_strip_all_tags($saved), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ),
        );
        if ($text !== '') {
            break;
        }
    }
    $position = mb_stripos($text, $query);
    $start = $position === false ? 0 : max(0, $position - 50);
    $excerpt = ($start ? '…' : '') . mb_substr($text, $start, 180);
    return (string) apply_filters('pagenest_search_excerpt', $excerpt, $post, $query);
}
