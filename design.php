<?php
/** Scoped presentation for native theme settings and Customizer controls. */
defined('ABSPATH') || exit();
add_action(
    'wp_enqueue_scripts',
    static function () {
        $manifest = require __DIR__ . '/assets/manifest.php';
        wp_enqueue_style(
            'pagenest-editorial',
            get_theme_file_uri('assets/' . $manifest['editorial']),
            ['pagenest', 'pagenest-appearance'],
            null,
        );
    },
    40,
);
add_action('admin_enqueue_scripts', static function ($screen) {
    if ($screen !== 'appearance_page_pagenest') {
        return;
    }
    $manifest = require __DIR__ . '/assets/manifest.php';
    wp_enqueue_style(
        'pagenest-admin-design',
        get_theme_file_uri('assets/' . $manifest['admin_design']),
        [],
        null,
    );
});
add_action('customize_controls_enqueue_scripts', static function () {
    $manifest = require __DIR__ . '/assets/manifest.php';
    wp_enqueue_style(
        'pagenest-customizer-design',
        get_theme_file_uri('assets/' . $manifest['customizer_design']),
        [],
        null,
    );
    wp_enqueue_script(
        'pagenest-customizer-design',
        get_theme_file_uri('assets/' . $manifest['customizer_design_js']),
        ['customize-controls'],
        null,
        true,
    );
});
add_action(
    'customize_register',
    static function ($customize) {
        $customize->add_panel('pagenest_design', [
            'title' => __('PageNest design', 'pagenest'),
            'description' => __(
                'Start with the reading appearance, arrange your home page, then finish the site details. Preview changes before publishing.',
                'pagenest',
            ),
            'priority' => 30,
        ]);
        foreach (
            [
                'pagenest_appearance',
                'pagenest_home_composition',
                'pagenest_home_copy',
                'pagenest_home_image',
                'pagenest_home_topics',
                'pagenest_home_authors',
                'pagenest_browsing',
                'pagenest_login',
                'pagenest_footer',
                'pagenest_navigation',
                'pagenest_community',
            ]
            as $position => $id
        ) {
            $section = $customize->get_section($id);
            if ($section) {
                $section->panel = 'pagenest_design';
                $section->priority = 10 * ($position + 1);
            }
        }
    },
    99,
);
