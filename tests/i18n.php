<?php
/** Exercise native localization hooks without WordPress database or remote services. */
define('ABSPATH', __DIR__);
require __DIR__ . '/i18n-stubs.php';
$hooks = [];
$enqueued = [];
$localized = [];
function add_action($hook, $callback, $priority = 10)
{
    global $hooks;
    $hooks[$hook][$priority] = $callback;
}
function wp_script_is($handle, $status)
{
    global $enqueued;
    return $status === 'enqueued' && in_array($handle, $enqueued, true);
}
function wp_localize_script($handle, $name, $strings)
{
    global $localized;
    $localized[$handle] = [$name, $strings];
}
function number_format_i18n($number)
{
    return number_format($number);
}
function i18n_check($condition, $label)
{
    if (!$condition) {
        throw new RuntimeException($label);
    }
}
require __DIR__ . '/../i18n.php';
i18n_check(
    isset($hooks['wp_enqueue_scripts'][100], $hooks['admin_enqueue_scripts'][100]),
    'late frontend and editor hooks',
);
pagenest_localize_scripts();
i18n_check($localized === [], 'no unneeded script registration');
$enqueued = [
    'pagenest-content-preview',
    'pagenest-reading',
    'pagenest-reader-preferences',
    'pagenest-discovery',
];
$hooks['admin_enqueue_scripts'][100]();
i18n_check(
    count($localized) === 4 &&
        $localized['pagenest-content-preview'][1]['tableLabel'] === '可横向滚动的表格',
    'editor reuses active Chinese catalog',
);
i18n_check(
    $localized['pagenest-reading'][1]['toggleHeading'] === '展开或收起：%s',
    'dynamic heading remains a placeholder',
);
$fixture_locale = 'en_US';
$localized = [];
pagenest_localize_scripts();
i18n_check(
    $localized['pagenest-reader-preferences'][1]['saved'] === 'Reading preferences saved.',
    'English script feedback',
);
i18n_check(
    pagenest_comment_count(0) === '0 comments' &&
        pagenest_comment_count(1) === '1 comment' &&
        pagenest_comment_count(2) === '2 comments',
    'English plural forms',
);
$fixture_locale = 'zh_CN';
i18n_check(
    pagenest_comment_count(1) === '1 条评论' && pagenest_comment_count(2) === '2 条评论',
    'Chinese plural form',
);
$fixture_locale = 'fr_FR';
i18n_check(
    pagenest_script_translations()['pagenest-reading']['expandAll'] === 'Expand all',
    'unsupported locale falls back to source',
);
echo "Locale hooks / editor / catalog / plurals / fallback passed\n";
