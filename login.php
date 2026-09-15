<?php
defined('ABSPATH') || exit();
function wzfj_login_notice_default()
{
    return '欢迎登录。你可以使用邮箱注册；如需帮助，请联系本站管理员。';
}
add_filter('login_body_class', static function ($classes) {
    $classes[] = 'wzfj-login';
    return $classes;
});
add_action('login_enqueue_scripts', static function () {
    $manifest = require __DIR__ . '/assets/manifest.php';
    wp_enqueue_style('wzfj-login', get_theme_file_uri('assets/' . $manifest['login']), [], null);
    $image = get_theme_mod('wzfj_login_background', '');
    if ($image) {
        wp_add_inline_style(
            'wzfj-login',
            'body.wzfj-login{--wzfj-login-image:url("' . esc_url_raw($image) . '")}',
        );
    }
});
add_filter('login_message', static function ($message) {
    $notice = get_theme_mod('wzfj_login_notice', wzfj_login_notice_default());
    if (trim($notice) === '') {
        return $message;
    }
    $links = get_option('users_can_register')
        ? '<p><a href="' . esc_url(wp_registration_url()) . '">使用邮箱注册</a></p>'
        : '';
    return $message .
        '<div class="wzfj-login-notice">' .
        wpautop(esc_html($notice)) .
        $links .
        '</div>';
});
add_filter('login_headerurl', static fn() => home_url('/'));
