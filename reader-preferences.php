<?php
if (!defined('ABSPATH')) {
    exit();
}
/** Visitor preferences are local to the browser, separate from theme settings. */
function pagenest_reader_preferences_controls()
{
    if (!is_singular('post') || post_password_required()) {
        return;
    }
    $groups = [
        'size' => ['字号', ['default' => '默认', '18' => '18', '20' => '20', '22' => '22']],
        'width' => ['正文宽度', ['default' => '默认', 'compact' => '紧凑', 'wide' => '舒展']],
        'palette' => ['阅读配色', ['default' => '默认', 'paper' => '暖纸', 'dark' => '暗色']],
    ];
    echo '<details class="pagenest-reader-preferences" data-pagenest-reader-preferences hidden><summary>阅读设置</summary><div class="pagenest-reader-options">';
    foreach ($groups as $key => [$label, $options]) {
        echo '<label>' .
            esc_html($label) .
            '<select data-reader-preference="' .
            esc_attr($key) .
            '">';
        foreach ($options as $value => $text) {
            echo '<option value="' . esc_attr($value) . '">' . esc_html($text) . '</option>';
        }
        echo '</select></label>';
    }
    echo '<button type="button" data-reader-reset>恢复默认</button><p>仅在此浏览器保存阅读偏好。</p><span class="pagenest-reader-status" role="status" aria-live="polite"></span></div></details>';
}
add_action('pagenest_reading_actions', 'pagenest_reader_preferences_controls', 20);
add_action('wp_enqueue_scripts', static function () {
    if (!is_singular('post') || post_password_required()) {
        return;
    }
    $manifest = require __DIR__ . '/assets/manifest.php';
    wp_enqueue_style(
        'pagenest-reader-preferences',
        get_theme_file_uri('assets/' . $manifest['reader_preferences_css']),
        ['pagenest-content'],
        null,
    );
    wp_enqueue_script(
        'pagenest-reader-preferences',
        get_theme_file_uri('assets/' . $manifest['reader_preferences_js']),
        [],
        null,
        true,
    );
});
