<?php
/** Execute the login module with registration settings and the shipped translations. */
define('ABSPATH', __DIR__);
require __DIR__ . '/i18n-stubs.php';
$filters = [];
$mods = [];
$registration = false;
$checks = 0;
function add_filter($hook, $callback)
{
    global $filters;
    $filters[$hook] = $callback;
}
function add_action($hook, $callback) {}
function get_option($key)
{
    global $registration;
    if ($key !== 'users_can_register') {
        throw new RuntimeException('Unexpected option lookup');
    }
    return $registration;
}
function get_theme_mod($key, $default = false)
{
    global $mods;
    return array_key_exists($key, $mods) ? $mods[$key] : $default;
}
function esc_html($value)
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function esc_url($value)
{
    return esc_html($value);
}
function wpautop($value)
{
    return '<p>' . $value . '</p>';
}
function wp_registration_url()
{
    return '/wp-login.php?action=register';
}
function login_check($condition, $label)
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}
require __DIR__ . '/../login.php';
foreach (['en_US', 'zh_CN'] as $fixture_locale) {
    foreach ([false, true] as $registration) {
        $mods = [];
        $expected = $registration
            ? ($fixture_locale === 'zh_CN'
                ? '欢迎登录。你可以使用邮箱注册；如需帮助，请联系本站管理员。'
                : 'Welcome. You can register with your email address. Contact the site administrator if you need help.')
            : ($fixture_locale === 'zh_CN'
                ? '欢迎登录。如需帮助，请联系本站管理员。'
                : 'Welcome. Contact the site administrator if you need help.');
        login_check(
            pagenest_login_notice_default() === $expected,
            'registration-aware localized default',
        );
        $message = $filters['login_message']('<p>Core login message</p>');
        login_check(str_contains($message, $expected), 'real login hook uses default');
        login_check(str_starts_with($message, '<p>Core login message</p>'), 'core message remains');
        login_check(
            str_contains($message, 'action=register') === $registration,
            'registration link follows switch',
        );
        $mods = ['pagenest_login_notice' => 'Custom <strong>notice</strong>'];
        $message = $filters['login_message']('Core');
        login_check(
            str_contains($message, esc_html($mods['pagenest_login_notice'])) &&
                !str_contains($message, $expected),
            'explicit custom notice remains and is escaped',
        );
        foreach (['', '   '] as $empty) {
            $mods = ['pagenest_login_notice' => $empty];
            login_check(
                $filters['login_message']('Core') === 'Core',
                'explicit empty notice hides default and link',
            );
            login_check($mods['pagenest_login_notice'] === $empty, 'saved value is unchanged');
        }
    }
}
echo $checks . " real login module registration / custom / empty / locale checks passed\n";
