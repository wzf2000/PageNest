<?php
defined('ABSPATH') || exit();
function wzfj_image_setting($value)
{
    $id = absint($value);
    return !$id || wp_attachment_is_image($id) ? $id : 0;
}
add_action('customize_register', static function ($wp_customize) {
    $wp_customize->add_section('wzfj_footer', ['title' => '页脚信息', 'priority' => 33]);
    foreach (
        [
            'wzfj_footer_owner' => ['版权署名', get_bloginfo('name')],
            'wzfj_footer_record' => ['备案号（可选）', ''],
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
            'section' => 'wzfj_footer',
            'type' => 'text',
        ]);
    }

    $wp_customize->add_section('wzfj_login', ['title' => '登录与注册', 'priority' => 32]);
    $wp_customize->add_setting('wzfj_login_background', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
        'capability' => 'edit_theme_options',
    ]);
    $wp_customize->add_control(
        new WP_Customize_Image_Control($wp_customize, 'wzfj_login_background', [
            'label' => '背景图片',
            'section' => 'wzfj_login',
        ]),
    );
    $wp_customize->add_setting('wzfj_login_notice', [
        'default' => wzfj_login_notice_default(),
        'sanitize_callback' => 'sanitize_textarea_field',
        'capability' => 'edit_theme_options',
    ]);
    $wp_customize->add_control('wzfj_login_notice', [
        'label' => '提示语',
        'description' => '显示在登录、注册和找回密码表单上方；留空即可隐藏。',
        'section' => 'wzfj_login',
        'type' => 'textarea',
    ]);
    $wp_customize->add_panel('wzfj_home', [
        'title' => '栖页首页设置',
        'description' => '调整首页背景、专题与作者展示。保存前可在右侧预览。',
        'priority' => 30,
    ]);
    $wp_customize->add_section('wzfj_home_image', ['title' => '首页背景', 'panel' => 'wzfj_home']);
    $wp_customize->add_setting('wzfj_hero_attachment', [
        'default' => 0,
        'sanitize_callback' => 'wzfj_image_setting',
        'capability' => 'edit_theme_options',
    ]);
    $wp_customize->add_control(
        new WP_Customize_Media_Control($wp_customize, 'wzfj_hero_attachment', [
            'label' => '背景图片',
            'description' => '从媒体库选择图片；清除后使用渐变背景。',
            'section' => 'wzfj_home_image',
            'mime_type' => 'image',
        ]),
    );
    $wp_customize->add_section('wzfj_home_topics', [
        'title' => '首页专题',
        'description' => '最多六项；名称或链接留空时不显示该项。',
        'panel' => 'wzfj_home',
    ]);
    for ($i = 0; $i < 6; $i++) {
        foreach (
            [
                'title' => ['名称', 'text', 'sanitize_text_field'],
                'url' => ['链接', 'url', 'esc_url_raw'],
            ]
            as $key => $field
        ) {
            $id = "wzfj_topics[$i][$key]";
            $wp_customize->add_setting($id, [
                'default' => '',
                'sanitize_callback' => $field[2],
                'capability' => 'edit_theme_options',
            ]);
            $wp_customize->add_control($id, [
                'label' => '专题' . ($i + 1) . ' · ' . $field[0],
                'section' => 'wzfj_home_topics',
                'type' => $field[1],
            ]);
        }
    }
    $wp_customize->add_section('wzfj_home_authors', [
        'title' => '首页作者',
        'description' => '最多七位；清空姓名即可隐藏该项，不影响网站账号。',
        'panel' => 'wzfj_home',
    ]);
    for ($i = 0; $i < 7; $i++) {
        $name = "wzfj_authors[$i][name]";
        $image = "wzfj_authors[$i][attachment_id]";
        $wp_customize->add_setting($name, [
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
            'capability' => 'edit_theme_options',
        ]);
        $wp_customize->add_control($name, [
            'label' => '作者' . ($i + 1) . ' · 姓名',
            'section' => 'wzfj_home_authors',
            'type' => 'text',
        ]);
        $wp_customize->add_setting($image, [
            'default' => 0,
            'sanitize_callback' => 'wzfj_image_setting',
            'capability' => 'edit_theme_options',
        ]);
        $wp_customize->add_control(
            new WP_Customize_Media_Control($wp_customize, $image, [
                'label' => '作者' . ($i + 1) . ' · 头像',
                'section' => 'wzfj_home_authors',
                'mime_type' => 'image',
            ]),
        );
    }
});
