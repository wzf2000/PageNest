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
    static $instance = 0;
    $instance++;
    $groups = [
        'size' => [
            __('Font size', 'pagenest'),
            ['default' => __('Default', 'pagenest'), '18' => '18', '20' => '20', '22' => '22'],
        ],
        'width' => [
            __('Article width', 'pagenest'),
            [
                'default' => __('Default', 'pagenest'),
                'compact' => __('Compact', 'pagenest'),
                'wide' => __('Wide', 'pagenest'),
            ],
        ],
        'palette' => [
            __('Reading palette', 'pagenest'),
            [
                'default' => __('Default', 'pagenest'),
                'paper' => __('Warm paper', 'pagenest'),
                'dark' => __('Dark', 'pagenest'),
            ],
        ],
    ];
    echo '<details class="pagenest-reader-preferences" data-pagenest-reader-preferences hidden><summary>' .
        esc_html__('Reading settings', 'pagenest') .
        '</summary><div class="pagenest-reader-options">';
    foreach ($groups as $key => [$label, $options]) {
        echo '<fieldset class="pagenest-reader-group"><legend>' .
            esc_html($label) .
            '</legend><div class="pagenest-reader-choices pagenest-reader-choices-' .
            esc_attr($key) .
            '">';
        foreach ($options as $value => $text) {
            echo '<label class="pagenest-reader-choice"><input type="radio" name="pagenest-reader-' .
                esc_attr($key . '-' . $instance) .
                '" data-reader-preference="' .
                esc_attr($key) .
                '" value="' .
                esc_attr($value) .
                '"' .
                ($value === 'default' ? ' checked' : '') .
                '>';
            if ($key === 'palette') {
                echo '<span aria-hidden="true" class="pagenest-palette-preview" data-preview="' .
                    esc_attr($value) .
                    '"></span>';
            }
            echo '<span>' . esc_html($text) . '</span></label>';
        }
        echo '</div></fieldset>';
    }
    echo '<div class="pagenest-reader-footer"><button type="button" data-reader-reset>' .
        esc_html__('Restore defaults', 'pagenest') .
        '</button><p><span class="pagenest-reader-hint">' .
        esc_html__('Device reading preferences are kept in this browser.', 'pagenest') .
        '</span><span class="pagenest-reader-status" role="status" aria-live="polite"></span></p></div></div></details>';
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
