<?php
/** Read-only Font Library contracts with disposable local files; no downloads. */
define('ABSPATH', __DIR__ . '/');
$enabled = true;
$mods = $hooks = [];
$posts = [];
$font_dir = sys_get_temp_dir() . '/pagenest-fonts-' . bin2hex(random_bytes(6));
mkdir($font_dir);
function post_type_exists($type)
{
    return $GLOBALS['enabled'] && in_array($type, ['wp_font_family', 'wp_font_face'], true);
}
function wp_get_font_dir()
{
    return [
        'basedir' => $GLOBALS['font_dir'],
        'baseurl' => $GLOBALS['font_base'] ?? 'https://example.test/fonts',
        'error' => false,
    ];
}
function wp_parse_url($url)
{
    return parse_url($url);
}
function home_url($path = '')
{
    return 'https://example.test' . $path;
}
function get_post($id)
{
    return $GLOBALS['posts'][$id] ?? null;
}
function get_posts($args)
{
    return array_values(
        array_filter(
            $GLOBALS['posts'],
            static fn($post) => $post->post_type === $args['post_type'] &&
                ($args['post_type'] !== 'wp_font_face' ||
                    $post->post_parent === $args['post_parent']),
        ),
    );
}
function get_theme_mod($key, $default)
{
    return $GLOBALS['mods'][$key] ?? $default;
}
function absint($value)
{
    return abs((int) $value);
}
function wp_strip_all_tags($text)
{
    return strip_tags($text);
}
function add_action($hook, $callback)
{
    $GLOBALS['hooks'][$hook][] = $callback;
}
function font_check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
require dirname(__DIR__) . '/fonts.php';
$posts[7] = (object) [
    'ID' => 7,
    'post_type' => 'wp_font_family',
    'post_status' => 'publish',
    'post_title' => 'Installed family',
];
$posts[8] = (object) [
    'ID' => 8,
    'post_parent' => 7,
    'post_type' => 'wp_font_face',
    'post_status' => 'publish',
    'post_content' => '',
];
foreach (
    ['ttf' => "\x00\x01\x00\x00", 'otf' => 'OTTO', 'woff' => 'wOFF', 'woff2' => 'wOF2']
    as $ext => $magic
) {
    file_put_contents($font_dir . '/sample.' . $ext, $magic . 'fixture');
    font_check(
        pagenest_font_file('https://example.test/fonts/sample.' . $ext) !== '',
        'native local formats accepted',
    );
}
foreach (
    [
        'https://outside.test/fonts/sample.ttf',
        'http://example.test/fonts/sample.ttf',
        'https://example.test/other/sample.ttf',
        'https://example.test/fonts/../sample.ttf',
        'https://example.test/fonts/%2e%2e/sample.ttf',
        'https://example.test/fonts/sample.ttf?x=1',
        'https://example.test/fonts/sample.ttf#x',
        'file:///tmp/sample.ttf',
        'https://example.test/fonts/sample.ttf");}body{color:red}/*',
        [],
    ]
    as $bad
) {
    font_check(pagenest_font_file($bad) === '', 'remote traversal query and CSS sources rejected');
}
$font_base = 'http://example.test/fonts';
font_check(
    pagenest_font_file('http://example.test/fonts/sample.ttf') === '',
    'font directory must share site scheme',
);
unset($font_base);
$outside = $font_dir . '-outside.ttf';
file_put_contents($outside, "\x00\x01\x00\x00");
symlink($outside, $font_dir . '/escape.ttf');
font_check(
    pagenest_font_file('https://example.test/fonts/escape.ttf') === '',
    'symlink outside native directory rejected',
);
file_put_contents($font_dir . '/fake.ttf', '<?php');
font_check(
    pagenest_font_file('https://example.test/fonts/fake.ttf') === '',
    'non-font contents rejected',
);
$posts[8]->post_content = json_encode([
    'fontFamily' => '";body{color:red}/*',
    'fontWeight' => '100 900',
    'fontStyle' => 'normal',
    'src' => 'https://example.test/fonts/sample.ttf',
]);
font_check(pagenest_fonts_css() === '', 'unconfigured fonts emit no faces');
$mods = ['pagenest_font_body' => 7, 'pagenest_font_heading' => 7];
$css = pagenest_fonts_css();
font_check(
    substr_count($css, '@font-face') === 1 &&
        str_contains($css, 'PageNestLibrary7') &&
        str_contains($css, 'font-display:swap') &&
        str_contains($css, 'sans-serif') &&
        !str_contains($css, 'color:red'),
    'selected faces deduplicated, aliases and fallback stacks remain safe',
);
$good = $posts[8]->post_content;
foreach (
    [
        ['fontWeight' => '400; color:red'],
        ['fontWeight' => '900 100'],
        ['fontStyle' => []],
        ['unicodeRange' => 'U+0000;body{}'],
        ['src' => ['https://example.test/fonts/sample.ttf', 'https://outside.test/remote.ttf']],
    ]
    as $changes
) {
    $posts[8]->post_content = json_encode(array_replace(json_decode($good, true), $changes));
    font_check(pagenest_fonts_css() === '', 'invalid face metadata falls back');
}
$posts[8]->post_content = $good;
$posts[7]->post_status = 'draft';
font_check(pagenest_fonts_css() === '', 'unpublished family falls back');
$posts[7]->post_status = 'publish';
unlink($font_dir . '/sample.ttf');
font_check(pagenest_fonts_css() === '', 'missing font file falls back immediately');
$enabled = false;
font_check(
    !pagenest_fonts_available() && pagenest_fonts_css() === '',
    'old WordPress capability fallback',
);
foreach (glob($font_dir . '/*') as $file) {
    unlink($file);
}
unlink($outside);
rmdir($font_dir);
echo "Native local fonts, CSS constraints, selected faces and fallback contracts passed\n";
