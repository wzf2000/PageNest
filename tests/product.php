<?php
/** Synthetic admin contracts: no WordPress database, private files or network. */
define('ABSPATH', __DIR__);
if (is_file(__DIR__ . '/i18n-stubs.php')) {
    require __DIR__ . '/i18n-stubs.php';
} else {
    function __($text, $domain = '')
    {
        return $text;
    }
    function esc_html__($text, $domain = '')
    {
        return esc_html(__($text, $domain));
    }
}
$mods = [
    'pagenest_appearance_background' => '',
    'pagenest_hero_attachment' => 4321,
    'foreign_mod' => ['keep' => true],
    'pagenest_footer_tagline' => '',
];
$writes = 0;
$saveFails = false;
$allowed = true;
$nonce = true;
$hooks = [];
$patterns = [];
function add_action($tag, $callback, $priority = 10)
{
    global $hooks;
    $hooks[$tag][] = $callback;
}
function add_filter($tag, $callback)
{
    add_action($tag, $callback);
}
function get_theme_mods()
{
    global $mods;
    return $mods;
}
function get_theme_mod($key, $default = false)
{
    global $mods;
    return $mods[$key] ?? $default;
}
function get_stylesheet()
{
    return 'synthetic-child-theme';
}
function get_template()
{
    return 'pagenest';
}
function update_option($key, $value)
{
    global $mods, $writes, $saveFails;
    if ($saveFails) {
        return false;
    }
    product_check($key === 'theme_mods_synthetic-child-theme', 'active stylesheet option');
    $mods = $value;
    $writes++;
    return true;
}
function current_user_can($cap)
{
    global $allowed;
    return $allowed;
}
function check_admin_referer($action)
{
    global $nonce;
    product_check($action === 'pagenest_appearance_transfer', 'nonce action');
    if (!$nonce) {
        throw new RuntimeException('nonce');
    }
}
function wp_die($message, $title = '', $args = [])
{
    throw new RuntimeException((string) ($args['response'] ?? $message));
}
class WP_Error
{
    public function __construct(public $code, public $message) {}
    public function get_error_message()
    {
        return $this->message;
    }
}
function is_wp_error($value)
{
    return $value instanceof WP_Error;
}
function esc_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES);
}
function esc_url($value)
{
    return esc_html($value);
}
function admin_url($path)
{
    return '/wp-admin/' . $path;
}
function add_query_arg($key, $value, $url)
{
    return $url . '?' . http_build_query([$key => $value]);
}
function wp_nonce_field($action)
{
    echo '<input name="_wpnonce" value="synthetic">';
}
function submit_button($label, $type = '')
{
    echo '<p class="submit"><button class="button ' .
        ($type === 'secondary' ? 'button-secondary' : 'button-primary') .
        '" type="submit">' .
        esc_html($label) .
        '</button></p>';
}
function wp_get_theme($template)
{
    return new class {
        public function get($key)
        {
            return '1.2.0-rc.3';
        }
    };
}
function get_bloginfo($key)
{
    return '6.0';
}
function register_block_pattern_category($key, $args)
{
    product_check($key === 'pagenest', 'pattern category');
}
function register_block_pattern($key, $args)
{
    global $patterns;
    $patterns[$key] = $args;
}
function product_check($value, $message)
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}
function product_rejected($callback, $expected)
{
    try {
        $callback();
    } catch (RuntimeException $error) {
        product_check($error->getMessage() === $expected, 'rejected with ' . $expected);
        return;
    }
    throw new RuntimeException('Expected rejection: ' . $expected);
}
require __DIR__ . '/../appearance.php';
require __DIR__ . '/../product.php';
$before = $mods;
$export = pagenest_product_export();
product_check(
    (array) $export['settings'] === ['pagenest_appearance_background' => ''],
    'export whitelist and explicit blanks',
);
product_check($writes === 0 && $mods === $before, 'export reads only');
$_SERVER['REQUEST_METHOD'] = 'POST';
$allowed = false;
product_rejected('pagenest_product_authorize_transfer', '403');
product_rejected('pagenest_product_page', '403');
$allowed = true;
$_SERVER['REQUEST_METHOD'] = 'GET';
product_rejected('pagenest_product_authorize_transfer', '405');
$_SERVER['REQUEST_METHOD'] = 'POST';
$nonce = false;
product_rejected('pagenest_product_authorize_transfer', 'nonce');
$nonce = true;
pagenest_product_authorize_transfer();
$payload = [
    'format' => 'pagenest-appearance',
    'version' => 1,
    'settings' => [
        'pagenest_appearance_preset' => 'forest',
        'pagenest_appearance_background' => '',
    ],
];
foreach (
    [
        ['unknown' => true],
        ['pagenest_hero_attachment' => 42],
        ['pagenest_appearance_font-size' => 999],
        ['pagenest_appearance_font-size' => '17'],
        ['pagenest_appearance_reading-line-height' => 1.55],
        ['pagenest_appearance_preset' => 'invented'],
        ['pagenest_appearance_accent' => '<script>'],
    ]
    as $bad
) {
    $candidate = $payload;
    $candidate['settings'] = array_merge($candidate['settings'], $bad);
    product_check(
        is_wp_error(pagenest_product_import_json(json_encode($candidate))),
        'reject bad setting',
    );
    product_check($writes === 0 && $mods === $before, 'invalid import atomic');
}
foreach (
    [
        '{',
        str_repeat('a', 16385),
        '{"format":"pagenest-appearance","version":2,"settings":{}}',
        '{"format":"pagenest-appearance","version":1,"settings":[]}',
        '{"format":"pagenest-appearance","version":1,"settings":{},"extra":true}',
    ]
    as $bad
) {
    product_check(is_wp_error(pagenest_product_import_json($bad)), 'reject invalid schema');
    product_check($writes === 0 && $mods === $before, 'schema error no writes');
}
product_check(
    pagenest_product_import_json(json_encode($payload)) === true && $writes === 1,
    'valid import single write',
);
product_check(
    $mods === array_merge($before, $payload['settings']),
    'omitted and unknown mods remain, blanks preserved',
);
product_check(
    pagenest_product_import_json('{"format":"pagenest-appearance","version":1,"settings":{}}') ===
        true && $writes === 1,
    'empty import no write',
);
$after = $mods;
product_check(
    pagenest_product_import_json(json_encode($payload)) === true && $writes === 1,
    'unchanged import succeeds without write',
);
$saveFails = true;
$failed = $payload;
$failed['settings']['pagenest_appearance_preset'] = 'warm';
product_check(
    is_wp_error(pagenest_product_import_json(json_encode($failed))) && $mods === $after,
    'failed persistence is reported without changes',
);
$saveFails = false;
pagenest_product_patterns();
product_check(count($patterns) === 3, 'three editable patterns');
foreach ($patterns as $pattern) {
    product_check(
        str_contains($pattern['content'], '<!-- wp:heading -->') &&
            !preg_match('/https?:|"id"|<img|anchor|wp:pagenest/', $pattern['content']),
        'portable core blocks, no identifiers or remote assets',
    );
}
product_check(!in_array(false, pagenest_product_diagnostics(), true), 'manifest hashes valid');
$temp = sys_get_temp_dir() . '/pagenest-product-' . getmypid();
mkdir($temp . '/assets', 0700, true);
file_put_contents(
    $temp . '/assets/manifest.php',
    '<?php return ["css"=>"layout-000000000000.css","js"=>"../../private.js"];',
);
file_put_contents($temp . '/assets/layout-000000000000.css', 'tampered');
$badAssets = pagenest_product_diagnostics($temp);
product_check(!$badAssets['css'] && !$badAssets['js'], 'tampering and path traversal rejected');
$staleHash = substr(hash('sha256', 'old'), 0, 12);
file_put_contents(
    $temp . '/assets/manifest.php',
    '<?php return ["css"=>"layout-' . $staleHash . '.css"];',
);
file_put_contents($temp . '/assets/layout-' . $staleHash . '.css', 'old');
file_put_contents($temp . '/assets/layout.css', 'new');
product_check(
    !pagenest_product_diagnostics($temp)['css'],
    'stale but internally valid manifest rejected',
);
file_put_contents($temp . '/assets/manifest.php', '<?php broken syntax !');
product_check(
    !pagenest_product_diagnostics($temp)['css'],
    'malformed manifest becomes diagnostic failure',
);
unlink($temp . '/assets/layout-' . $staleHash . '.css');
unlink($temp . '/assets/layout.css');
unlink($temp . '/assets/layout-000000000000.css');
unlink($temp . '/assets/manifest.php');
rmdir($temp . '/assets');
rmdir($temp);
ob_start();
pagenest_product_page();
$html = ob_get_clean();
product_check($mods === $after && $writes === 1, 'patterns diagnostics and hub no writes');
product_check(
    substr_count($html, 'method="post"') === 2 &&
        str_contains($html, 'autofocus%5Bsection%5D=pagenest_appearance'),
    'guarded POST forms and deep links',
);
$runtime = __DIR__ . '/../.runtime/frontend';
$manifest = require __DIR__ . '/../assets/manifest.php';
$native = getenv('PAGENEST_TEST_WP_ROOT') ?: '';
$adminHead = '<meta name="viewport" content="width=device-width, initial-scale=1">';
if ($native !== '' && is_dir($native . '/wp-admin/css')) {
    @mkdir($runtime . '/admin-core', 0777, true);
    foreach (['common', 'forms', 'themes'] as $asset) {
        copy(
            $native . '/wp-admin/css/' . $asset . '.css',
            $runtime . '/admin-core/' . $asset . '.css',
        );
        $adminHead .= '<link rel="stylesheet" href="/admin-core/' . $asset . '.css">';
    }
}
$adminHead .= '<link rel="stylesheet" href="/assets/' . $manifest['admin_design'] . '">';
if (!is_dir($runtime)) {
    mkdir($runtime, 0777, true);
}
file_put_contents(
    $runtime . '/product.html',
    '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><title>PageNest configuration</title>' .
        $adminHead .
        '<body class="wp-admin wp-core-ui"><div id="wpwrap"><div id="wpbody-content">' .
        $html .
        '</div></div></body></html>',
);
$fixture_locale = 'en_US';
ob_start();
pagenest_product_page();
$english = ob_get_clean();
product_check(
    str_contains($english, 'Local read-only diagnostics') &&
        !str_contains($english, '本地只读诊断'),
    'English hub and forms',
);
file_put_contents(
    $runtime . '/product-english.html',
    '<!doctype html><html lang="en-US"><meta charset="utf-8"><title>PageNest configuration</title>' .
        $adminHead .
        '<body class="wp-admin wp-core-ui"><div id="wpwrap"><div id="wpbody-content">' .
        $english .
        '</div></div></body></html>',
);
echo "Product hub / transfer / patterns / diagnostics contracts passed.\n";
