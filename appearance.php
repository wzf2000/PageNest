<?php
/** Shared, validated appearance values for the reader and editor previews. */
defined('ABSPATH') || exit();

function pagenest_appearance_presets()
{
    return [
        'default' => [
            'background' => '#f4f6f9',
            'surface' => '#ffffff',
            'text' => '#303b4a',
            'heading' => '#18324d',
            'content-heading' => '#172b43',
            'muted' => '#637387',
            'accent' => '#285f8f',
            'accent-hover' => '#123f66',
            'content-link' => '#2266a5',
            'content-link-hover' => '#164777',
            'focus' => '#2b78b8',
            'border' => '#e1e7ee',
            'soft' => '#edf3f9',
            'code-background' => '#f6f8fb',
            'code-text' => '#325578',
            'quote-background' => '#f3f7fb',
            'quote-border' => '#82a6c7',
        ],
        'warm' => [
            'background' => '#f5f1e9',
            'surface' => '#fffdf8',
            'text' => '#453c32',
            'heading' => '#46372a',
            'content-heading' => '#46372a',
            'muted' => '#756756',
            'accent' => '#84502d',
            'accent-hover' => '#60371d',
            'content-link' => '#84502d',
            'content-link-hover' => '#60371d',
            'focus' => '#84502d',
            'border' => '#e5dccf',
            'soft' => '#f2e9dd',
            'code-background' => '#f6f0e6',
            'code-text' => '#68452d',
            'quote-background' => '#f6f0e6',
            'quote-border' => '#b19070',
        ],
        'forest' => [
            'background' => '#f1f5f1',
            'surface' => '#ffffff',
            'text' => '#303e35',
            'heading' => '#254436',
            'content-heading' => '#254436',
            'muted' => '#607468',
            'accent' => '#2e674e',
            'accent-hover' => '#204b38',
            'content-link' => '#2e674e',
            'content-link-hover' => '#204b38',
            'focus' => '#2e674e',
            'border' => '#dce6de',
            'soft' => '#e8f1eb',
            'code-background' => '#f0f5f1',
            'code-text' => '#2e6049',
            'quote-background' => '#f0f5f1',
            'quote-border' => '#7ba58a',
        ],
    ];
}

function pagenest_appearance_numbers()
{
    return [
        'font-size' => [
            'label' => __('Base font size (px)', 'pagenest'),
            'default' => 16,
            'min' => 14,
            'max' => 20,
            'step' => 1,
            'unit' => 'px',
        ],
        'reading-size' => [
            'label' => __('Article font size (px)', 'pagenest'),
            'default' => 17,
            'min' => 15,
            'max' => 24,
            'step' => 1,
            'unit' => 'px',
        ],
        'reading-line-height' => [
            'label' => __('Article line height', 'pagenest'),
            'default' => 1.9,
            'min' => 1.5,
            'max' => 2.2,
            'step' => 0.1,
            'unit' => '',
        ],
        'spacing' => [
            'label' => __('Spacing scale', 'pagenest'),
            'default' => 1,
            'min' => 0.8,
            'max' => 1.2,
            'step' => 0.1,
            'unit' => '',
        ],
        'radius' => [
            'label' => __('Card corner radius (px)', 'pagenest'),
            'default' => 12,
            'min' => 0,
            'max' => 24,
            'step' => 1,
            'unit' => 'px',
        ],
        'container' => [
            'label' => __('Maximum site width (px)', 'pagenest'),
            'default' => 1240,
            'min' => 1040,
            'max' => 1440,
            'step' => 10,
            'unit' => 'px',
        ],
    ];
}

function pagenest_sanitize_appearance_preset($value)
{
    return is_string($value) && isset(pagenest_appearance_presets()[$value]) ? $value : 'default';
}

function pagenest_sanitize_appearance_color($value)
{
    // Only literal hex colors can reach CSS. Empty values defer to the selected preset.
    if (!is_string($value) || !preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/iD', $value)) {
        return '';
    }
    return strtolower($value);
}

function pagenest_sanitize_appearance_number($value, $definition)
{
    if (!is_scalar($value) || is_bool($value) || !is_numeric($value)) {
        return $definition['default'];
    }
    $number = (float) $value;
    if (!is_finite($number)) {
        return $definition['default'];
    }
    $number = max($definition['min'], min($definition['max'], $number));
    return round(round($number / $definition['step']) * $definition['step'], 2);
}

function pagenest_appearance_variables()
{
    $preset = pagenest_sanitize_appearance_preset(
        get_theme_mod('pagenest_appearance_preset', 'default'),
    );
    $colors = pagenest_appearance_presets()[$preset];
    foreach (['background', 'text', 'accent'] as $key) {
        $color = pagenest_sanitize_appearance_color(
            get_theme_mod('pagenest_appearance_' . $key, ''),
        );
        if ($color !== '') {
            $colors[$key] = $color;
            if ($key === 'accent') {
                $colors['content-link'] = $color;
                $colors['focus'] = $color;
            }
        }
    }
    $variables = [];
    foreach ($colors as $key => $value) {
        $variables['--pagenest-' . $key] = $value;
    }
    foreach (pagenest_appearance_numbers() as $key => $definition) {
        $value = pagenest_sanitize_appearance_number(
            get_theme_mod('pagenest_appearance_' . $key, $definition['default']),
            $definition,
        );
        $variables['--pagenest-' . $key] = $value . $definition['unit'];
    }
    $variables['--pagenest-font-scale'] = (float) $variables['--pagenest-font-size'] / 16;
    return $variables;
}

function pagenest_appearance_css()
{
    $declarations = [];
    foreach (pagenest_appearance_variables() as $key => $value) {
        $declarations[] = $key . ':' . $value;
    }
    return ':root{' . implode(';', $declarations) . '}';
}

function pagenest_register_appearance_customizer($wp_customize)
{
    $wp_customize->add_section('pagenest_appearance', [
        'title' => __('Site appearance', 'pagenest'),
        'description' => __(
            'Share colors and reading dimensions across the site and editor preview. Leave custom colors empty to use the selected palette.',
            'pagenest',
        ),
        'priority' => 31,
    ]);
    $wp_customize->add_setting('pagenest_appearance_preset', [
        'default' => 'default',
        'sanitize_callback' => 'pagenest_sanitize_appearance_preset',
        'capability' => 'edit_theme_options',
        'transport' => 'refresh',
    ]);
    $wp_customize->add_control('pagenest_appearance_preset', [
        'label' => __('Color palette', 'pagenest'),
        'section' => 'pagenest_appearance',
        'type' => 'select',
        'choices' => [
            'default' => __('Default', 'pagenest'),
            'warm' => __('Warm paper', 'pagenest'),
            'forest' => __('Forest', 'pagenest'),
        ],
    ]);
    foreach (
        [
            'background' => __('Site background', 'pagenest'),
            'text' => __('Text color', 'pagenest'),
            'accent' => __('Link and button color', 'pagenest'),
        ]
        as $key => $label
    ) {
        $id = 'pagenest_appearance_' . $key;
        $wp_customize->add_setting($id, [
            'default' => '',
            'sanitize_callback' => 'pagenest_sanitize_appearance_color',
            'capability' => 'edit_theme_options',
            'transport' => 'refresh',
        ]);
        $wp_customize->add_control(
            new WP_Customize_Color_Control($wp_customize, $id, [
                'label' => $label,
                'section' => 'pagenest_appearance',
            ]),
        );
    }
    foreach (pagenest_appearance_numbers() as $key => $definition) {
        $id = 'pagenest_appearance_' . $key;
        $wp_customize->add_setting($id, [
            'default' => $definition['default'],
            'sanitize_callback' => static fn($value) => pagenest_sanitize_appearance_number(
                $value,
                $definition,
            ),
            'capability' => 'edit_theme_options',
            'transport' => 'refresh',
        ]);
        $wp_customize->add_control($id, [
            'label' => $definition['label'],
            'section' => 'pagenest_appearance',
            'type' => 'number',
            'input_attrs' => array_intersect_key($definition, array_flip(['min', 'max', 'step'])),
        ]);
    }
}

function pagenest_enqueue_appearance()
{
    $manifest = require __DIR__ . '/assets/manifest.php';
    $asset = $manifest['appearance'] ?? 'appearance.css';
    wp_enqueue_style(
        'pagenest-appearance',
        get_theme_file_uri('assets/' . $asset),
        [],
        $asset === 'appearance.css' ? filemtime(__DIR__ . '/assets/appearance.css') : null,
    );
    wp_add_inline_style('pagenest-appearance', pagenest_appearance_css());
}
add_action('wp_enqueue_scripts', 'pagenest_enqueue_appearance', 20);
add_action(
    'admin_enqueue_scripts',
    static function ($screen) {
        if (in_array($screen, ['post.php', 'post-new.php'], true)) {
            pagenest_enqueue_appearance();
        }
    },
    20,
);
// Gutenberg uses a separate canvas; insert the same validated values into that canvas.
add_filter('block_editor_settings_all', static function ($settings) {
    $settings['styles'][] = ['css' => pagenest_appearance_css()];
    return $settings;
});
add_filter('tiny_mce_before_init', static function ($settings) {
    $settings['content_style'] =
        ($settings['content_style'] ?? '') . "\n" . pagenest_appearance_css();
    return $settings;
});
