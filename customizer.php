<?php
defined('ABSPATH') || exit();
function pagenest_image_setting($value)
{
    $id = absint($value);
    return !$id || wp_attachment_is_image($id) ? $id : 0;
}
add_action('customize_register', static function ($wp_customize) {
    if (function_exists('pagenest_register_appearance_customizer')) {
        pagenest_register_appearance_customizer($wp_customize);
    }
    $wp_customize->add_section('pagenest_footer', [
        'title' => __('Footer information', 'pagenest'),
        'priority' => 33,
    ]);
    $wp_customize->add_section('pagenest_navigation', [
        'title' => __('Archive and about links', 'pagenest'),
        'priority' => 34,
    ]);
    $wp_customize->add_section('pagenest_community', [
        'title' => __('Community', 'pagenest'),
        'priority' => 35,
    ]);
    $defaults = pagenest_setting_defaults();
    foreach (
        [
            'pagenest_footer_tagline' => [
                __('Footer tagline', 'pagenest'),
                'pagenest_footer',
                'text',
            ],
            'pagenest_community_description' => [
                __('Community description', 'pagenest'),
                'pagenest_community',
                'textarea',
            ],
            'pagenest_community_link_label' => [
                __('Community link label', 'pagenest'),
                'pagenest_community',
                'text',
            ],
            'pagenest_community_link_url' => [
                __('Community link URL', 'pagenest'),
                'pagenest_community',
                'url',
            ],
            'pagenest_community_archive_label' => [
                __('Community archive label', 'pagenest'),
                'pagenest_community',
                'text',
            ],
            'pagenest_archive_link_label' => [
                __('Archive link label', 'pagenest'),
                'pagenest_navigation',
                'text',
            ],
            'pagenest_archive_link_url' => [
                __('Archive link URL', 'pagenest'),
                'pagenest_navigation',
                'url',
            ],
            'pagenest_about_link_label' => [
                __('About link label', 'pagenest'),
                'pagenest_navigation',
                'text',
            ],
            'pagenest_about_link_url' => [
                __('About link URL', 'pagenest'),
                'pagenest_navigation',
                'url',
            ],
        ]
        as $id => $field
    ) {
        $wp_customize->add_setting($id, [
            'default' => $defaults[$id],
            'sanitize_callback' =>
                $field[2] === 'url'
                    ? 'esc_url_raw'
                    : ($field[2] === 'textarea'
                        ? 'sanitize_textarea_field'
                        : 'sanitize_text_field'),
            'capability' => 'edit_theme_options',
        ]);
        $wp_customize->add_control($id, [
            'label' => $field[0],
            'description' => __(
                'Leave empty to hide the text or link. Links need both a label and a URL.',
                'pagenest',
            ),
            'section' => $field[1],
            'type' => $field[2],
        ]);
    }
    foreach (
        [
            'pagenest_footer_owner' => [__('Copyright owner', 'pagenest'), get_bloginfo('name')],
            'pagenest_footer_record' => [__('Registration number (optional)', 'pagenest'), ''],
        ]
        as $id => $field
    ) {
        $wp_customize->add_setting($id, [
            'default' => $field[1],
            'sanitize_callback' => 'sanitize_text_field',
            'capability' => 'edit_theme_options',
        ]);
        $wp_customize->add_control($id, [
            'label' => $field[0],
            'section' => 'pagenest_footer',
            'type' => 'text',
        ]);
    }

    $wp_customize->add_section('pagenest_login', [
        'title' => __('Login and registration', 'pagenest'),
        'priority' => 32,
    ]);
    $wp_customize->add_setting('pagenest_login_background', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
        'capability' => 'edit_theme_options',
    ]);
    $wp_customize->add_control(
        new WP_Customize_Image_Control($wp_customize, 'pagenest_login_background', [
            'label' => __('Background image', 'pagenest'),
            'section' => 'pagenest_login',
        ]),
    );
    $wp_customize->add_setting('pagenest_login_notice', [
        'default' => pagenest_login_notice_default(),
        'sanitize_callback' => 'sanitize_textarea_field',
        'capability' => 'edit_theme_options',
    ]);
    $wp_customize->add_control('pagenest_login_notice', [
        'label' => __('Notice', 'pagenest'),
        'description' => __(
            'Shown above login, registration and password reset forms; leave empty to hide.',
            'pagenest',
        ),
        'section' => 'pagenest_login',
        'type' => 'textarea',
    ]);
    $wp_customize->add_panel('pagenest_home', [
        'title' => __('PageNest home page', 'pagenest'),
        'description' => __(
            'Customize the home background, topics and authors. Preview your changes before saving.',
            'pagenest',
        ),
        'priority' => 30,
    ]);
    $wp_customize->add_section('pagenest_home_copy', [
        'title' => __('Home introduction', 'pagenest'),
        'panel' => 'pagenest_home',
    ]);
    foreach (
        [
            'pagenest_home_eyebrow' => [__('Home eyebrow', 'pagenest'), 'text'],
            'pagenest_home_intro' => [__('Home introduction', 'pagenest'), 'textarea'],
            'pagenest_authors_intro' => [__('Author introduction', 'pagenest'), 'textarea'],
        ]
        as $id => $field
    ) {
        $wp_customize->add_setting($id, [
            'default' => $defaults[$id],
            'sanitize_callback' =>
                $field[1] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
            'capability' => 'edit_theme_options',
        ]);
        $wp_customize->add_control($id, [
            'label' => $field[0],
            'description' => __(
                'Leave empty to hide. Introductions support line breaks.',
                'pagenest',
            ),
            'section' => 'pagenest_home_copy',
            'type' => $field[1],
        ]);
    }
    $wp_customize->add_section('pagenest_home_image', [
        'title' => __('Home background', 'pagenest'),
        'panel' => 'pagenest_home',
    ]);
    $wp_customize->add_setting('pagenest_hero_attachment', [
        'default' => 0,
        'sanitize_callback' => 'pagenest_image_setting',
        'capability' => 'edit_theme_options',
    ]);
    $wp_customize->add_control(
        new WP_Customize_Media_Control($wp_customize, 'pagenest_hero_attachment', [
            'label' => __('Background image', 'pagenest'),
            'description' => __(
                'Choose your own media; clear it for a text-focused introduction.',
                'pagenest',
            ),
            'section' => 'pagenest_home_image',
            'mime_type' => 'image',
        ]),
    );
    $wp_customize->add_section('pagenest_home_topics', [
        'title' => __('Home topics', 'pagenest'),
        'description' => __(
            'Up to six topics. Leave a name or link empty to hide the entry.',
            'pagenest',
        ),
        'panel' => 'pagenest_home',
    ]);
    for ($i = 0; $i < 6; $i++) {
        foreach (
            [
                'title' => [__('Name', 'pagenest'), 'text', 'sanitize_text_field'],
                'url' => [__('Links', 'pagenest'), 'url', 'esc_url_raw'],
                'description' => [
                    __('Description (optional)', 'pagenest'),
                    'textarea',
                    'sanitize_textarea_field',
                ],
                'status' => [__('Status (optional)', 'pagenest'), 'text', 'sanitize_text_field'],
            ]
            as $key => $field
        ) {
            $id = "pagenest_topics[$i][$key]";
            $wp_customize->add_setting($id, [
                'default' => '',
                'sanitize_callback' => $field[2],
                'capability' => 'edit_theme_options',
            ]);
            $wp_customize->add_control($id, [
                'label' => sprintf(__("Topic %1\$d · %2\$s", 'pagenest'), $i + 1, $field[0]),
                'section' => 'pagenest_home_topics',
                'type' => $field[1],
            ]);
        }
    }
    $wp_customize->add_section('pagenest_home_authors', [
        'title' => __('Home authors', 'pagenest'),
        'description' => __(
            'Up to seven authors. Clear a name to hide its entry without changing the site account.',
            'pagenest',
        ),
        'panel' => 'pagenest_home',
    ]);
    for ($i = 0; $i < 7; $i++) {
        $name = "pagenest_authors[$i][name]";
        $image = "pagenest_authors[$i][attachment_id]";
        $wp_customize->add_setting($name, [
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
            'capability' => 'edit_theme_options',
        ]);
        $wp_customize->add_control($name, [
            'label' => sprintf(__('Author %d · Name', 'pagenest'), $i + 1),
            'section' => 'pagenest_home_authors',
            'type' => 'text',
        ]);
        $wp_customize->add_setting($image, [
            'default' => 0,
            'sanitize_callback' => 'pagenest_image_setting',
            'capability' => 'edit_theme_options',
        ]);
        $wp_customize->add_control(
            new WP_Customize_Media_Control($wp_customize, $image, [
                'label' => sprintf(__('Author %d · Avatar', 'pagenest'), $i + 1),
                'section' => 'pagenest_home_authors',
                'mime_type' => 'image',
            ]),
        );
    }
});
