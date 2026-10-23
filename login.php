<?php
defined('ABSPATH') || exit();
function pagenest_login_notice_default()
{
    if (!get_option('users_can_register')) {
        return __('Welcome. Contact the site administrator if you need help.', 'pagenest');
    }
    return __(
        'Welcome. You can register with your email address. Contact the site administrator if you need help.',
        'pagenest',
    );
}
add_filter('login_body_class', static function ($classes) {
    $classes[] = 'pagenest-login';
    return $classes;
});
add_action('login_enqueue_scripts', static function () {
    $manifest = require __DIR__ . '/assets/manifest.php';
    wp_enqueue_style(
        'pagenest-login',
        get_theme_file_uri('assets/' . $manifest['login']),
        [],
        null,
    );
    wp_enqueue_script(
        'pagenest-login',
        get_theme_file_uri('assets/' . $manifest['login_js']),
        [],
        null,
        true,
    );
    $image = get_theme_mod('pagenest_login_background', '');
    if ($image) {
        wp_add_inline_style(
            'pagenest-login',
            'body.pagenest-login{--pagenest-login-image:url("' . esc_url_raw($image) . '")}',
        );
    }
});
add_filter('login_message', static function ($message) {
    $notice = get_theme_mod('pagenest_login_notice', pagenest_login_notice_default());
    if (trim($notice) === '') {
        return $message;
    }
    $links = get_option('users_can_register')
        ? '<p><a href="' .
            esc_url(wp_registration_url()) .
            '">' .
            esc_html__('Register with email', 'pagenest') .
            '</a></p>'
        : '';
    return $message .
        '<div class="pagenest-login-notice">' .
        wpautop(esc_html($notice)) .
        $links .
        '</div>';
});
add_filter('login_headerurl', static fn() => home_url('/'));
