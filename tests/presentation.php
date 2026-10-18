<?php
/** Presentation choices are bounded and cannot grant tool or content access. */
define('ABSPATH', __DIR__);
$hooks = $meta = $mods = [];
$tool = false;
$type = 'page';
$writes = 0;
function add_action($name, $fn, ...$args)
{
    $GLOBALS['hooks'][$name][] = $fn;
}
function add_filter(...$args)
{
    add_action(...$args);
}
function apply_filters($name, $value, ...$args)
{
    return $name === 'pagenest_tool_page' ? $GLOBALS['tool'] : $value;
}
function __($text, $domain)
{
    return $text;
}
function get_post_type($id)
{
    return $GLOBALS['type'];
}
function get_post_meta($id, $key, $single)
{
    return $GLOBALS['meta'][$id][$key] ?? '';
}
function get_theme_mod($key, $default)
{
    return $GLOBALS['mods'][$key] ?? $default;
}
function wp_is_post_revision($id)
{
    return false;
}
function wp_is_post_autosave($id)
{
    return false;
}
function current_user_can($cap, $id)
{
    return $GLOBALS['authorized'] ?? true;
}
function wp_verify_nonce($value, $action)
{
    return $value === 'valid';
}
function sanitize_text_field($value)
{
    return strip_tags($value);
}
function wp_unslash($value)
{
    return $value;
}
function update_post_meta($id, $key, $value)
{
    $GLOBALS['meta'][$id][$key] = $value;
    $GLOBALS['writes']++;
}
function delete_post_meta($id, $key)
{
    unset($GLOBALS['meta'][$id][$key]);
    $GLOBALS['writes']++;
}
require dirname(__DIR__) . '/presentation.php';
$checks = 0;
function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $GLOBALS['checks']++;
}
check(
    pagenest_color_scheme('system') === 'system' && pagenest_color_scheme('invalid') === 'light',
    'Scheme enum is bounded and preserves light default',
);
check(
    pagenest_layout_selection(1) === ['topic', 'standard'],
    'Existing pages retain standard layout',
);
$tool = true;
check(
    pagenest_layout_selection(1) === ['tool', 'standard'],
    'Registered tools use tool defaults without new permissions',
);
$mods['pagenest_layout_tool'] = 'workspace';
check(pagenest_layout_selection(1) === ['tool', 'workspace'], 'Tool workspace is opt-in');
$meta[1]['_pagenest_layout'] = ['context' => 'topic', 'preset' => 'overview'];
check(
    pagenest_layout_selection(1) === ['topic', 'overview'],
    'Explicit page purpose and preset override only presentation',
);
$meta[1]['_pagenest_layout'] = ['context' => 'topic', 'preset' => 'workspace'];
check(
    pagenest_layout_selection(1) === ['topic', 'standard'],
    'Inapplicable preset falls back safely',
);
$type = 'post';
$meta[1]['_pagenest_layout'] = ['context' => 'tool', 'preset' => 'workspace'];
check(
    pagenest_layout_selection(1) === ['article', 'standard'],
    'Article cannot become a tool through layout metadata',
);
$_POST = ['pagenest-layout-nonce' => 'invalid', 'pagenest_layout' => ['preset' => 'wide']];
$save = end($hooks['save_post']);
$save(1);
check($writes === 0, 'Invalid nonce never changes metadata');
$_POST['pagenest-layout-nonce'] = 'valid';
$authorized = false;
$save(1);
check($writes === 0, 'Unauthorized editor never changes metadata');
$authorized = true;
$save(1);
check(
    $meta[1]['_pagenest_layout'] === ['context' => 'article', 'preset' => 'wide'],
    'Authorized save writes only bounded neutral presentation metadata',
);
check($tool === true, 'Layout changes do not enable or disable registered tools');
echo "$checks presentation contracts passed\n";
