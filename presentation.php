<?php
/** Site-wide color mode and opt-in layout presets, separate from article reading preferences. */
defined('ABSPATH') || exit();
function pagenest_color_scheme($value): string
{
    return in_array($value, ['light', 'dark', 'system'], true) ? $value : 'light';
}
function pagenest_layout_choices(string $context): array
{
    $standard = ['standard' => __('Standard', 'pagenest')];
    return $standard +
        match ($context) {
            'article' => [
                'focused' => __('Focused reading', 'pagenest'),
                'wide' => __('Wide article', 'pagenest'),
            ],
            'tool' => [
                'workspace' => __('Tool workspace', 'pagenest'),
                'focused' => __('Compact tool', 'pagenest'),
            ],
            default => [
                'overview' => __('Topic overview', 'pagenest'),
                'wide' => __('Wide page', 'pagenest'),
            ],
        };
}
function pagenest_layout_context(int $id): string
{
    if (get_post_type($id) === 'post') {
        return 'article';
    }
    $value = get_post_meta($id, '_pagenest_layout', true);
    if (is_array($value) && in_array($value['context'] ?? '', ['topic', 'tool'], true)) {
        return $value['context'];
    }
    return apply_filters('pagenest_tool_page', false, $id) ? 'tool' : 'topic';
}
function pagenest_layout_selection(int $id): array
{
    $context = pagenest_layout_context($id);
    $stored = get_post_meta($id, '_pagenest_layout', true);
    $preset = is_array($stored) ? $stored['preset'] ?? 'default' : 'default';
    if ($preset === 'default') {
        $preset = get_theme_mod('pagenest_layout_' . $context, 'standard');
    }
    return [
        $context,
        array_key_exists(is_string($preset) ? $preset : '', pagenest_layout_choices($context))
            ? $preset
            : 'standard',
    ];
}
add_filter('body_class', static function ($classes) {
    if (!is_singular(['post', 'page'])) {
        return $classes;
    }
    [$context, $preset] = pagenest_layout_selection((int) get_queried_object_id());
    $classes[] = 'pagenest-context-' . $context;
    $classes[] = 'pagenest-layout-' . $preset;
    return $classes;
});
function pagenest_initialize_color_scheme(): void
{
    $scheme = pagenest_color_scheme(get_theme_mod('pagenest_color_scheme', 'light'));
    echo '<script>(function(){var mode=' .
        wp_json_encode($scheme) .
        ';try{var stored=JSON.parse(localStorage.getItem("pagenest-site-appearance-v1"));if(stored&&["light","dark","system"].includes(stored.scheme))mode=stored.scheme;}catch(e){}document.documentElement.dataset.pagenestColorScheme=mode;})();</script>';
}
add_action('wp_head', 'pagenest_initialize_color_scheme', 1);
add_action('login_head', 'pagenest_initialize_color_scheme', 1);
add_action('login_enqueue_scripts', static function () {
    $manifest = require __DIR__ . '/assets/manifest.php';
    wp_enqueue_style(
        'pagenest-presentation',
        get_theme_file_uri('assets/' . $manifest['presentation_css']),
        ['pagenest-login'],
        null,
    );
    wp_add_inline_style('pagenest-presentation', pagenest_appearance_css());
});
add_action(
    'pagenest_header_actions',
    static function () {
        static $instance = 0;
        $instance++;
        echo '<details class="pagenest-site-preferences" data-pagenest-site-preferences hidden><summary>' .
            esc_html__('Appearance', 'pagenest') .
            '</summary><div><fieldset class="pagenest-appearance-group"><legend>' .
            esc_html__('Site color mode', 'pagenest') .
            '</legend><div class="pagenest-appearance-choices">';
        foreach (
            [
                'default' => __('Site default', 'pagenest'),
                'light' => __('Light', 'pagenest'),
                'dark' => __('Dark', 'pagenest'),
                'system' => __('Follow system', 'pagenest'),
            ]
            as $value => $label
        ) {
            echo '<label class="pagenest-appearance-choice"><input type="radio" name="pagenest-color-mode-' .
                $instance .
                '" data-pagenest-color-mode value="' .
                esc_attr($value) .
                '"' .
                ($value === 'default' ? ' checked' : '') .
                '><span class="pagenest-appearance-preview" data-preview="' .
                esc_attr($value) .
                '" aria-hidden="true"><i></i><i></i><i></i></span><span>' .
                esc_html($label) .
                '</span></label>';
        }
        echo '</div></fieldset><p>' .
            esc_html__(
                'Applies across this site in this browser. Article reading settings remain available separately.',
                'pagenest',
            ) .
            '</p><span role="status" aria-live="polite"></span></div></details>';
    },
    30,
);
add_action(
    'wp_enqueue_scripts',
    static function () {
        $manifest = require __DIR__ . '/assets/manifest.php';
        wp_enqueue_style(
            'pagenest-presentation',
            get_theme_file_uri('assets/' . $manifest['presentation_css']),
            ['pagenest-appearance'],
            null,
        );
        wp_enqueue_script(
            'pagenest-presentation',
            get_theme_file_uri('assets/' . $manifest['presentation_js']),
            [],
            null,
            true,
        );
        wp_localize_script('pagenest-presentation', 'PageNestPresentation', [
            'defaultScheme' => pagenest_color_scheme(
                get_theme_mod('pagenest_color_scheme', 'light'),
            ),
            'saved' => __('Appearance preference saved in this browser.', 'pagenest'),
            'unsaved' => __('Applied to this page; browser storage is unavailable.', 'pagenest'),
        ]);
    },
    30,
);
add_action(
    'customize_register',
    static function ($customizer) {
        $customizer->add_setting('pagenest_color_scheme', [
            'default' => 'light',
            'sanitize_callback' => 'pagenest_color_scheme',
            'capability' => 'edit_theme_options',
        ]);
        $customizer->add_control('pagenest_color_scheme', [
            'section' => 'pagenest_appearance',
            'label' => __('Default site color mode', 'pagenest'),
            'type' => 'select',
            'choices' => [
                'light' => __('Light', 'pagenest'),
                'dark' => __('Dark', 'pagenest'),
                'system' => __('Follow system', 'pagenest'),
            ],
        ]);
        $customizer->add_section('pagenest_layouts', [
            'title' => __('Page layouts', 'pagenest'),
            'description' => __(
                'Presets change presentation only. Existing content, tool permissions and reading controls are preserved.',
                'pagenest',
            ),
            'priority' => 32,
        ]);
        foreach (
            [
                'article' => __('Article layout', 'pagenest'),
                'topic' => __('Topic page layout', 'pagenest'),
                'tool' => __('Tool page layout', 'pagenest'),
            ]
            as $context => $label
        ) {
            $choices = pagenest_layout_choices($context);
            $customizer->add_setting('pagenest_layout_' . $context, [
                'default' => 'standard',
                'sanitize_callback' => static fn($value) => is_string($value) &&
                isset($choices[$value])
                    ? $value
                    : 'standard',
                'capability' => 'edit_theme_options',
            ]);
            $customizer->add_control('pagenest_layout_' . $context, [
                'section' => 'pagenest_layouts',
                'label' => $label,
                'type' => 'select',
                'choices' => $choices,
            ]);
        }
    },
    20,
);
add_action('add_meta_boxes', static function () {
    foreach (['post', 'page'] as $type) {
        add_meta_box(
            'pagenest-layout',
            __('PageNest layout', 'pagenest'),
            static function ($post) {
                if (!current_user_can('edit_post', $post->ID)) {
                    return;
                }
                $value = get_post_meta($post->ID, '_pagenest_layout', true);
                $value = is_array($value) ? $value : [];
                $context = pagenest_layout_context($post->ID);
                wp_nonce_field('pagenest-layout-' . $post->ID, 'pagenest-layout-nonce');
                if ($post->post_type === 'page') {
                    echo '<p><label>' .
                        esc_html__('Page purpose', 'pagenest') .
                        '<select name="pagenest_layout[context]">';
                    foreach (
                        [
                            'auto' => __('Automatic', 'pagenest'),
                            'topic' => __('Topic or information page', 'pagenest'),
                            'tool' => __('Tool workspace', 'pagenest'),
                        ]
                        as $key => $label
                    ) {
                        echo '<option value="' .
                            esc_attr($key) .
                            '" ' .
                            selected($value['context'] ?? 'auto', $key, false) .
                            '>' .
                            esc_html($label) .
                            '</option>';
                    }
                    echo '</select></label></p>';
                }
                echo '<p><label>' .
                    esc_html__('Layout preset', 'pagenest') .
                    '<select name="pagenest_layout[preset]"><option value="default">' .
                    esc_html__('Use site layout default', 'pagenest') .
                    '</option>';
                // Show the union for pages so context and layout can be chosen in the same save.
                $choices =
                    $post->post_type === 'page'
                        ? pagenest_layout_choices('topic') + pagenest_layout_choices('tool')
                        : pagenest_layout_choices('article');
                foreach ($choices as $key => $label) {
                    echo '<option value="' .
                        esc_attr($key) .
                        '" ' .
                        selected($value['preset'] ?? 'default', $key, false) .
                        '>' .
                        esc_html($label) .
                        '</option>';
                }
                echo '</select></label></p><p>' .
                    esc_html__(
                        'Layout affects presentation only and never enables a tool or changes access. Invalid combinations use the standard layout.',
                        'pagenest',
                    ) .
                    '</p>';
            },
            $type,
            'side',
        );
    }
});
add_action('save_post', static function ($id) {
    if (
        wp_is_post_revision($id) ||
        wp_is_post_autosave($id) ||
        !in_array(get_post_type($id), ['post', 'page'], true) ||
        !current_user_can('edit_post', $id) ||
        !is_string($_POST['pagenest-layout-nonce'] ?? null) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['pagenest-layout-nonce'])),
            'pagenest-layout-' . $id,
        )
    ) {
        return;
    }
    $input = is_array($_POST['pagenest_layout'] ?? null)
        ? wp_unslash($_POST['pagenest_layout'])
        : [];
    $context =
        get_post_type($id) === 'post'
            ? 'article'
            : (in_array($input['context'] ?? '', ['auto', 'topic', 'tool'], true)
                ? $input['context']
                : 'auto');
    $allowed =
        pagenest_layout_choices('article') +
        pagenest_layout_choices('topic') +
        pagenest_layout_choices('tool');
    $preset =
        is_string($input['preset'] ?? null) && isset($allowed[$input['preset']])
            ? $input['preset']
            : 'default';
    if ($preset === 'default' && in_array($context, ['auto', 'article'], true)) {
        delete_post_meta($id, '_pagenest_layout');
    } else {
        update_post_meta($id, '_pagenest_layout', ['context' => $context, 'preset' => $preset]);
    }
});
