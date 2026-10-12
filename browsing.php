<?php
/** Optional home composition and list presentation; never changes stored content. */
defined('ABSPATH') || exit();
function pagenest_home_modules(): array
{
    return [
        'topics' => ['title' => __('Topics', 'pagenest'), 'enabled' => true, 'order' => 10],
        'latest' => [
            'title' => __('Latest articles', 'pagenest'),
            'enabled' => true,
            'order' => 20,
        ],
        'authors' => ['title' => __('Author', 'pagenest'), 'enabled' => true, 'order' => 30],
        'featured' => [
            'title' => __('Featured articles', 'pagenest'),
            'enabled' => false,
            'order' => 40,
        ],
        'updated' => [
            'title' => __('Recently updated', 'pagenest'),
            'enabled' => false,
            'order' => 50,
        ],
        'destinations' => [
            'title' => __('Content guide', 'pagenest'),
            'enabled' => false,
            'order' => 60,
        ],
    ];
}
function pagenest_browsing_bool($value): bool
{
    return in_array($value, [true, 1, '1'], true);
}
function pagenest_browsing_order($value): int
{
    return is_scalar($value) && is_numeric($value) ? max(0, min(99, (int) $value)) : 50;
}
function pagenest_browsing_id($value): int
{
    return is_scalar($value) && preg_match('/^[0-9]+$/D', (string) $value) ? absint($value) : 0;
}
function pagenest_list_style($value): string
{
    return in_array($value, ['standard', 'compact'], true) ? $value : 'standard';
}
function pagenest_home_order(): array
{
    $modules = pagenest_home_modules();
    foreach ($modules as $key => &$module) {
        $module['enabled'] = pagenest_browsing_bool(
            get_theme_mod('pagenest_home_' . $key . '_enabled', $module['enabled']),
        );
        $module['order'] = pagenest_browsing_order(
            get_theme_mod('pagenest_home_' . $key . '_order', $module['order']),
        );
    }
    unset($module);
    uasort($modules, static fn($a, $b) => $a['order'] <=> $b['order']);
    return array_keys(array_filter($modules, static fn($module) => $module['enabled']));
}
/** Home recommendations always have public visibility, including for administrators. */
function pagenest_home_public_post($post, string $type): bool
{
    return $post instanceof WP_Post &&
        $post->post_type === $type &&
        $post->post_status === 'publish' &&
        $post->post_password === '';
}
function pagenest_home_entries(string $module): array
{
    if (!in_array($module, ['latest', 'updated', 'featured', 'destinations'], true)) {
        return [];
    }
    $type = $module === 'destinations' ? 'page' : 'post';
    if (in_array($module, ['featured', 'destinations'], true)) {
        $result = [];
        for ($slot = 0; $slot < 4; $slot++) {
            $id = pagenest_browsing_id(get_theme_mod('pagenest_home_' . $module . '_' . $slot, 0));
            $post = $id ? get_post($id) : null;
            if (pagenest_home_public_post($post, $type)) {
                $result[$post->ID] = $post;
            }
        }
        return array_values($result);
    }
    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'has_password' => false,
        'posts_per_page' => 4,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'orderby' => [$module === 'updated' ? 'modified' : 'date' => 'DESC', 'ID' => 'DESC'],
    ]);
    return array_slice(
        array_values(
            array_filter(
                $query->posts,
                static fn($post) => pagenest_home_public_post($post, 'post'),
            ),
        ),
        0,
        4,
    );
}
function pagenest_browsing_metadata(int $id): void
{
    $post = get_post($id);
    if (!$post || post_password_required($post)) {
        return;
    }
    $updated = pagenest_browsing_bool(get_theme_mod('pagenest_show_updated', false));
    $duration = pagenest_browsing_bool(get_theme_mod('pagenest_show_reading_time', false));
    $parts = [];
    if ($updated && get_post_modified_time('U', true, $post) > get_post_time('U', true, $post)) {
        $parts[] =
            '<span>' .
            esc_html__('Updated on ', 'pagenest') .
            '<time datetime="' .
            esc_attr(get_the_modified_date(DATE_W3C, $post)) .
            '">' .
            esc_html(get_the_modified_date(__('F j, Y', 'pagenest'), $post)) .
            '</time></span>';
    }
    if ($duration && $post->post_content !== '') {
        // Count source text only: never render blocks, shortcodes, embeds or private providers.
        $text = wp_strip_all_tags(strip_shortcodes($post->post_content));
        $cjk = preg_match_all('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', $text);
        $other = preg_replace('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', ' ', $text);
        $words = preg_match_all('/[\p{L}\p{N}]+/u', $other ?? '');
        $minutes = max(1, (int) ceil(($cjk ?: 0) / 400 + ($words ?: 0) / 200));
        $parts[] =
            '<span>' .
            esc_html(
                sprintf(
                    _n('About %s minute to read', 'About %s minutes to read', $minutes, 'pagenest'),
                    number_format_i18n($minutes),
                ),
            ) .
            '</span>';
    }
    if ($parts) {
        echo '<div class="pagenest-reading-meta">' . implode('', $parts) . '</div>';
    }
}
function pagenest_home_post_section(string $module): void
{
    $entries = pagenest_home_entries($module);
    // Empty opt-in modules disappear instead of introducing dead ends.
    if (!$entries && $module !== 'latest') {
        return;
    }
    $labels = [
        'latest' => [__('Recent entries', 'pagenest'), __('Latest articles', 'pagenest')],
        'updated' => [__('Continuously updated', 'pagenest'), __('Recently updated', 'pagenest')],
        'featured' => [__('Recommended reading', 'pagenest'), __('Featured articles', 'pagenest')],
        'destinations' => [__('Explore by topic', 'pagenest'), __('Content guide', 'pagenest')],
    ];
    ?><section class="pagenest-home-section" data-pagenest-home-module="<?php echo esc_attr($module); ?>">
    <div class="pagenest-section-heading">
        <div>
            <p class="pagenest-eyebrow"><?php echo esc_html($labels[$module][0]); ?></p>
            <h2><?php echo esc_html($labels[$module][1]); ?></h2>
        </div><?php if ($module !== 'destinations'): ?><a href="<?php echo esc_url(
    pagenest_posts_url(),
); ?>"><?php echo esc_html__('All articles →', 'pagenest'); ?></a><?php endif; ?>
    </div>
    <div class="<?php echo $module === 'destinations'
        ? 'pagenest-topic-grid'
        : 'pagenest-home-posts'; ?>"><?php
foreach ($entries as $post) {
    if ($module !== 'destinations') {
        pagenest_card($post->ID, 'home');
        continue;
    } ?><a href="<?php echo esc_url(get_permalink($post)); ?>">
            <h3><?php echo esc_html($post->post_title); ?></h3><?php if (
    $post->post_excerpt !== ''
): ?><p><?php echo esc_html(
    wp_strip_all_tags(strip_shortcodes($post->post_excerpt)),
); ?></p><?php endif; ?><span><?php echo esc_html__('Explore →', 'pagenest'); ?></span>
        </a><?php
}
if (!$entries) {
    echo '<p>' . esc_html__('No public articles yet.', 'pagenest') . '</p>';
}?></div>
</section><?php
}
add_action('wp_enqueue_scripts', static function (): void {
    $manifest = require __DIR__ . '/assets/manifest.php';
    wp_enqueue_style(
        'pagenest-browsing',
        get_theme_file_uri('assets/' . $manifest['browsing']),
        ['pagenest'],
        null,
    );
});
add_action('customize_register', static function ($customizer): void {
    $customizer->add_section('pagenest_home_composition', [
        'title' => __('Modules and content order', 'pagenest'),
        'panel' => 'pagenest_home',
        'description' => __(
            'The site introduction is always shown. Lower order numbers appear first; ties keep the default order. Disabling a module hides it while preserving settings and content.',
            'pagenest',
        ),
    ]);
    $register = static function (
        $id,
        $label,
        $type,
        $default,
        $sanitize,
        $section,
        $extra = [],
    ) use ($customizer): void {
        $customizer->add_setting($id, [
            'default' => $default,
            'sanitize_callback' => $sanitize,
            'capability' => 'edit_theme_options',
        ]);
        $customizer->add_control(
            $id,
            array_merge(['label' => $label, 'type' => $type, 'section' => $section], $extra),
        );
    };
    foreach (pagenest_home_modules() as $key => $module) {
        $register(
            'pagenest_home_' . $key . '_enabled',
            sprintf(__('Show %s', 'pagenest'), $module['title']),
            'checkbox',
            $module['enabled'],
            'pagenest_browsing_bool',
            'pagenest_home_composition',
        );
        $register(
            'pagenest_home_' . $key . '_order',
            sprintf(__('%s · Order', 'pagenest'), $module['title']),
            'number',
            $module['order'],
            'pagenest_browsing_order',
            'pagenest_home_composition',
            ['input_attrs' => ['min' => 0, 'max' => 99, 'step' => 1]],
        );
    }
    foreach (['featured' => 'post', 'destinations' => 'page'] as $module => $type) {
        $choices = [0 => __('Hidden', 'pagenest')];
        $posts = get_posts([
            'post_type' => $type,
            'post_status' => 'publish',
            'has_password' => false,
            'numberposts' => 100,
            'orderby' => 'modified',
            'order' => 'DESC',
            'suppress_filters' => false,
        ]);
        foreach ($posts as $post) {
            if (pagenest_home_public_post($post, $type)) {
                $choices[$post->ID] = $post->post_title;
            }
        }
        // Keep older selections editable even when outside the bounded initial list.
        foreach (pagenest_home_entries($module) as $post) {
            $choices[$post->ID] = $post->post_title;
        }
        for ($slot = 0; $slot < 4; $slot++) {
            $register(
                'pagenest_home_' . $module . '_' . $slot,
                sprintf(
                    __("%1\$s · Slot %2\$d", 'pagenest'),
                    pagenest_home_modules()[$module]['title'],
                    $slot + 1,
                ),
                'select',
                0,
                'pagenest_browsing_id',
                'pagenest_home_composition',
                [
                    'choices' => $choices,
                    'description' =>
                        $module === 'featured'
                            ? __(
                                'The 100 most recently updated public, unprotected articles, plus current selections.',
                                'pagenest',
                            )
                            : __(
                                'Choose a public, unprotected page as a series, topic or tool entry. Its title and manual excerpt are used without copying content.',
                                'pagenest',
                            ),
                ],
            );
            $customizer->add_control('pagenest_home_' . $module . '_' . $slot . '_id', [
                'settings' => 'pagenest_home_' . $module . '_' . $slot,
                'section' => 'pagenest_home_composition',
                'label' => sprintf(__('Slot %d · Content ID (optional)', 'pagenest'), $slot + 1),
                'description' => __(
                    'Enter the ID after post= in the edit URL to select older content; 0 hides this slot. Only public, unprotected content of the correct type is eligible.',
                    'pagenest',
                ),
                'type' => 'number',
                'input_attrs' => ['min' => 0, 'step' => 1],
            ]);
        }
    }
    $customizer->add_section('pagenest_browsing', [
        'title' => __('Lists and articles', 'pagenest'),
        'priority' => 31,
    ]);
    $register(
        'pagenest_list_style',
        __('List style', 'pagenest'),
        'select',
        'standard',
        'pagenest_list_style',
        'pagenest_browsing',
        [
            'choices' => [
                'standard' => __('Standard cards', 'pagenest'),
                'compact' => __('Compact list', 'pagenest'),
            ],
        ],
    );
    foreach (
        [
            'pagenest_list_covers' => [__('Show list covers', 'pagenest'), true],
            'pagenest_article_cover' => [__('Show article cover', 'pagenest'), true],
            'pagenest_show_updated' => [__('Show updated date', 'pagenest'), false],
            'pagenest_show_reading_time' => [__('Show estimated reading time', 'pagenest'), false],
        ]
        as $id => $field
    ) {
        $register(
            $id,
            $field[0],
            'checkbox',
            $field[1],
            'pagenest_browsing_bool',
            'pagenest_browsing',
        );
    }
});
