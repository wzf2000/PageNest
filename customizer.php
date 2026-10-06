<?php
defined('ABSPATH') || exit();
function pagenest_image_setting($value)
{
    $id = absint($value);
    return !$id || wp_attachment_is_image($id) ? $id : 0;
}
add_action('customize_register', static function ($wp_customize) {
    $wp_customize->add_section('pagenest_footer', ['title' => '页脚信息', 'priority' => 33]);
    $wp_customize->add_section('pagenest_navigation', [
        'title' => '归档与关于链接',
        'priority' => 34,
    ]);
    $wp_customize->add_section('pagenest_community', [
        'title' => '交流区',
        'priority' => 35,
    ]);
    $defaults = pagenest_setting_defaults();
    foreach (
        [
            'pagenest_footer_tagline' => ['页脚标语', 'pagenest_footer', 'text'],
            'pagenest_community_description' => ['交流区说明', 'pagenest_community', 'textarea'],
            'pagenest_community_link_label' => ['交流链接名称', 'pagenest_community', 'text'],
            'pagenest_community_link_url' => ['交流链接地址', 'pagenest_community', 'url'],
            'pagenest_community_archive_label' => ['交流区归档名称', 'pagenest_community', 'text'],
            'pagenest_archive_link_label' => ['归档链接名称', 'pagenest_navigation', 'text'],
            'pagenest_archive_link_url' => ['归档链接地址', 'pagenest_navigation', 'url'],
            'pagenest_about_link_label' => ['关于链接名称', 'pagenest_navigation', 'text'],
            'pagenest_about_link_url' => ['关于链接地址', 'pagenest_navigation', 'url'],
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
            'description' => '留空可隐藏对应文案或链接；链接名称和地址均需填写。',
            'section' => $field[1],
            'type' => $field[2],
        ]);
    }
    foreach (
        [
            'pagenest_footer_owner' => ['版权署名', get_bloginfo('name')],
            'pagenest_footer_record' => ['备案号（可选）', ''],
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

    $wp_customize->add_section('pagenest_login', ['title' => '登录与注册', 'priority' => 32]);
    $wp_customize->add_setting('pagenest_login_background', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
        'capability' => 'edit_theme_options',
    ]);
    $wp_customize->add_control(
        new WP_Customize_Image_Control($wp_customize, 'pagenest_login_background', [
            'label' => '背景图片',
            'section' => 'pagenest_login',
        ]),
    );
    $wp_customize->add_setting('pagenest_login_notice', [
        'default' => pagenest_login_notice_default(),
        'sanitize_callback' => 'sanitize_textarea_field',
        'capability' => 'edit_theme_options',
    ]);
    $wp_customize->add_control('pagenest_login_notice', [
        'label' => '提示语',
        'description' => '显示在登录、注册和找回密码表单上方；留空即可隐藏。',
        'section' => 'pagenest_login',
        'type' => 'textarea',
    ]);
    $wp_customize->add_panel('pagenest_home', [
        'title' => '栖页首页设置',
        'description' => '调整首页背景、专题与作者展示。保存前可在右侧预览。',
        'priority' => 30,
    ]);
    $wp_customize->add_section('pagenest_home_copy', [
        'title' => '首页介绍',
        'panel' => 'pagenest_home',
    ]);
    foreach (
        [
            'pagenest_home_eyebrow' => ['首页眉题', 'text'],
            'pagenest_home_intro' => ['首页介绍', 'textarea'],
            'pagenest_authors_intro' => ['作者介绍', 'textarea'],
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
            'description' => '留空可隐藏；介绍支持换行。',
            'section' => 'pagenest_home_copy',
            'type' => $field[1],
        ]);
    }
    $wp_customize->add_section('pagenest_home_image', [
        'title' => '首页背景',
        'panel' => 'pagenest_home',
    ]);
    $wp_customize->add_setting('pagenest_hero_attachment', [
        'default' => 0,
        'sanitize_callback' => 'pagenest_image_setting',
        'capability' => 'edit_theme_options',
    ]);
    $wp_customize->add_control(
        new WP_Customize_Media_Control($wp_customize, 'pagenest_hero_attachment', [
            'label' => '背景图片',
            'description' => '从媒体库选择图片；清除后使用渐变背景。',
            'section' => 'pagenest_home_image',
            'mime_type' => 'image',
        ]),
    );
    $wp_customize->add_section('pagenest_home_topics', [
        'title' => '首页专题',
        'description' => '最多六项；名称或链接留空时不显示该项。',
        'panel' => 'pagenest_home',
    ]);
    for ($i = 0; $i < 6; $i++) {
        foreach (
            [
                'title' => ['名称', 'text', 'sanitize_text_field'],
                'url' => ['链接', 'url', 'esc_url_raw'],
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
                'label' => '专题' . ($i + 1) . ' · ' . $field[0],
                'section' => 'pagenest_home_topics',
                'type' => $field[1],
            ]);
        }
    }
    $wp_customize->add_section('pagenest_home_authors', [
        'title' => '首页作者',
        'description' => '最多七位；清空姓名即可隐藏该项，不影响网站账号。',
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
            'label' => '作者' . ($i + 1) . ' · 姓名',
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
                'label' => '作者' . ($i + 1) . ' · 头像',
                'section' => 'pagenest_home_authors',
                'mime_type' => 'image',
            ]),
        );
    }
});
