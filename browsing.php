<?php
/** Optional home composition and list presentation; never changes stored content. */
defined('ABSPATH') || exit();
function pagenest_home_modules(): array
{
    return [
        'topics' => ['title' => '专题', 'enabled' => true, 'order' => 10],
        'latest' => ['title' => '最新文章', 'enabled' => true, 'order' => 20],
        'authors' => ['title' => '作者', 'enabled' => true, 'order' => 30],
        'featured' => ['title' => '精选文章', 'enabled' => false, 'order' => 40],
        'updated' => ['title' => '最近更新', 'enabled' => false, 'order' => 50],
        'destinations' => ['title' => '内容导览', 'enabled' => false, 'order' => 60],
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
            '<span>更新于 <time datetime="' .
            esc_attr(get_the_modified_date(DATE_W3C, $post)) .
            '">' .
            esc_html(get_the_modified_date('Y年n月j日', $post)) .
            '</time></span>';
    }
    if ($duration && $post->post_content !== '') {
        // Count source text only: never render blocks, shortcodes, embeds or private providers.
        $text = wp_strip_all_tags(strip_shortcodes($post->post_content));
        $cjk = preg_match_all('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', $text);
        $other = preg_replace('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', ' ', $text);
        $words = preg_match_all('/[\p{L}\p{N}]+/u', $other ?? '');
        $minutes = max(1, (int) ceil(($cjk ?: 0) / 400 + ($words ?: 0) / 200));
        $parts[] = '<span>约 ' . esc_html($minutes) . ' 分钟阅读</span>';
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
        'latest' => ['最新记录', '最新文章'],
        'updated' => ['持续整理', '最近更新'],
        'featured' => ['推荐阅读', '精选文章'],
        'destinations' => ['按主题探索', '内容导览'],
    ];
    ?><section class="pagenest-home-section" data-pagenest-home-module="<?php echo esc_attr($module); ?>">
    <div class="pagenest-section-heading">
        <div>
            <p class="pagenest-eyebrow"><?php echo esc_html($labels[$module][0]); ?></p>
            <h2><?php echo esc_html($labels[$module][1]); ?></h2>
        </div><?php if ($module !== 'destinations'): ?><a href="<?php echo esc_url(
    pagenest_posts_url(),
); ?>">全部文章 →</a><?php endif; ?>
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
); ?></p><?php endif; ?><span>前往浏览 →</span>
        </a><?php
}
if (!$entries) {
    echo '<p>还没有公开文章。</p>';
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
        'title' => '模块与内容编排',
        'panel' => 'pagenest_home',
        'description' =>
            '站点介绍始终保留。模块顺序数值越小越靠前；相同时使用默认顺序。停用仅隐藏展示，保留配置和内容。',
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
            '显示' . $module['title'],
            'checkbox',
            $module['enabled'],
            'pagenest_browsing_bool',
            'pagenest_home_composition',
        );
        $register(
            'pagenest_home_' . $key . '_order',
            $module['title'] . ' · 顺序',
            'number',
            $module['order'],
            'pagenest_browsing_order',
            'pagenest_home_composition',
            ['input_attrs' => ['min' => 0, 'max' => 99, 'step' => 1]],
        );
    }
    foreach (['featured' => 'post', 'destinations' => 'page'] as $module => $type) {
        $choices = [0 => '不展示'];
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
                pagenest_home_modules()[$module]['title'] . ' · 位置 ' . ($slot + 1),
                'select',
                0,
                'pagenest_browsing_id',
                'pagenest_home_composition',
                [
                    'choices' => $choices,
                    'description' =>
                        $module === 'featured'
                            ? '最近更新的 100 篇公开无密码文章及现有选择。'
                            : '选择公开无密码页面作为系列、专题或工具入口；使用页面标题与手写摘要，不复制内容。',
                ],
            );
            $customizer->add_control('pagenest_home_' . $module . '_' . $slot . '_id', [
                'settings' => 'pagenest_home_' . $module . '_' . $slot,
                'section' => 'pagenest_home_composition',
                'label' => '位置 ' . ($slot + 1) . ' · 内容 ID（可选）',
                'description' =>
                    '也可填写编辑页面地址中 post= 后的 ID，以选择更早的内容；0 表示不展示。仅公开无密码且类型正确的内容有效。',
                'type' => 'number',
                'input_attrs' => ['min' => 0, 'step' => 1],
            ]);
        }
    }
    $customizer->add_section('pagenest_browsing', ['title' => '列表与文章展示', 'priority' => 31]);
    $register(
        'pagenest_list_style',
        '列表样式',
        'select',
        'standard',
        'pagenest_list_style',
        'pagenest_browsing',
        ['choices' => ['standard' => '标准卡片', 'compact' => '紧凑列表']],
    );
    foreach (
        [
            'pagenest_list_covers' => ['显示列表封面', true],
            'pagenest_article_cover' => ['显示文章大封面', true],
            'pagenest_show_updated' => ['显示更新日期', false],
            'pagenest_show_reading_time' => ['显示估算阅读时长', false],
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
