<?php
/** Category-only display metadata; no database, network or content writes. */
define('ABSPATH', __DIR__);
$hooks = $term_meta = $mods = [];
$taxonomy = 'category';
$authorized = $nonce = $category = true;
$writes = 0;
function add_action($name, $callback, ...$args)
{
    $GLOBALS['hooks'][$name][] = $callback;
}
function __($text, $domain)
{
    return $text;
}
function esc_html($text)
{
    return htmlspecialchars($text, ENT_QUOTES);
}
function esc_attr($text)
{
    return esc_html($text);
}
function esc_html__($text, $domain)
{
    return esc_html($text);
}
function selected($value, $choice, $echo = false)
{
    return $value === $choice ? 'selected' : '';
}
function get_term($id, $taxonomy)
{
    return $GLOBALS['taxonomy']
        ? (object) ['taxonomy' => $GLOBALS['taxonomy'], 'term_id' => $id]
        : null;
}
function get_term_meta($id, $key, $single)
{
    return $GLOBALS['term_meta'][$id][$key] ?? '';
}
function update_term_meta($id, $key, $value)
{
    $GLOBALS['term_meta'][$id][$key] = $value;
    $GLOBALS['writes']++;
}
function delete_term_meta($id, $key)
{
    unset($GLOBALS['term_meta'][$id][$key]);
    $GLOBALS['writes']++;
}
function current_user_can($cap, $id)
{
    category_check($cap === 'edit_term' && $id === 7, 'native edit_term capability');
    return $GLOBALS['authorized'];
}
function wp_verify_nonce($value, $action)
{
    category_check($action === 'pagenest-listing-7', 'term-bound nonce');
    return $GLOBALS['nonce'] && $value === 'valid';
}
function wp_nonce_field($action, $name)
{
    echo '<input name="' . $name . '" value="valid">';
}
function wp_unslash($value)
{
    return $value;
}
function is_wp_error($value)
{
    return false;
}
function is_404()
{
    return false;
}
function is_search()
{
    return false;
}
function is_home()
{
    return false;
}
function is_archive()
{
    return true;
}
function is_category()
{
    return $GLOBALS['category'];
}
function get_queried_object_id()
{
    return 7;
}
function get_theme_mod($key, $default)
{
    return $GLOBALS['mods'][$key] ?? $default;
}
function category_check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
require dirname(__DIR__) . '/browsing.php';
category_check(
    $hooks['category_edit_form_fields'][0] === 'pagenest_category_listing_fields' &&
        $hooks['edited_category'][0] === 'pagenest_save_category_listing',
    'only native category edit hooks registered',
);
$term_meta = [
    7 => [
        'foreign' => ['keep' => true],
        '_pagenest_listing' => ['layout' => 'grid-3', 'sidebar' => 'inherit'],
    ],
];
$mods = [
    'pagenest_listing_archive_layout' => 'compact',
    'pagenest_listing_archive_sidebar' => 'hide',
];
category_check(
    pagenest_listing_presentation() === ['layout' => 'grid-3', 'sidebar' => false],
    'category layout and inherited archive sidebar',
);
$term_meta[7]['_pagenest_listing'] = ['layout' => 'inherit', 'sidebar' => 'show'];
category_check(
    pagenest_listing_presentation() === ['layout' => 'compact', 'sidebar' => true],
    'category sidebar and inherited archive layout',
);
$category = false;
category_check(
    pagenest_listing_presentation() === ['layout' => 'compact', 'sidebar' => false],
    'noncategory archives ignore category metadata',
);
$category = true;
$mods = ['pagenest_list_style' => 'compact'];
$term_meta[7]['_pagenest_listing'] = ['layout' => 'inherit', 'sidebar' => 'inherit'];
category_check(
    pagenest_listing_presentation() === ['layout' => 'compact', 'sidebar' => true],
    'inherit chain reaches old list style and sidebar',
);
$term_meta[7]['_pagenest_listing'] = ['layout' => [], 'sidebar' => new stdClass()];
category_check(
    pagenest_listing_presentation() === ['layout' => 'compact', 'sidebar' => true],
    'malformed stored values inherit',
);
$term_meta[7]['_pagenest_listing'] = ['layout' => 'standard', 'sidebar' => 'hide'];
$before = $term_meta;
foreach (
    [
        [],
        ['layout' => 'grid-2'],
        ['sidebar' => 'hide'],
        ['layout' => [], 'sidebar' => 'show'],
        ['layout' => false, 'sidebar' => 'show'],
        ['layout' => 'grid-4', 'sidebar' => 'hide'],
        ['layout' => 'grid-2', 'sidebar' => 'invalid'],
    ]
    as $input
) {
    $_POST = ['pagenest-listing-nonce' => 'valid', 'pagenest_listing' => $input];
    pagenest_save_category_listing(7);
    category_check(
        $term_meta === $before && $writes === 0,
        'missing or invalid fields preserve all metadata',
    );
}
$_POST = [
    'pagenest-listing-nonce' => 'valid',
    'pagenest_listing' => ['layout' => 'grid-2', 'sidebar' => 'hide'],
];
foreach (['capability', 'nonce', 'tag', 'missing-term'] as $case) {
    $authorized = $case !== 'capability';
    $nonce = $case !== 'nonce';
    $taxonomy = $case === 'tag' ? 'post_tag' : ($case === 'missing-term' ? '' : 'category');
    pagenest_save_category_listing(7);
    category_check(
        $term_meta === $before && $writes === 0,
        'rejected unauthorized or noncategory save',
    );
}
$authorized = $nonce = true;
$taxonomy = 'category';
foreach ([null, [], 'invalid'] as $bad_nonce) {
    $_POST['pagenest-listing-nonce'] = $bad_nonce;
    pagenest_save_category_listing(7);
    category_check(
        $term_meta === $before && $writes === 0,
        'missing malformed or invalid nonce rejected',
    );
}
$_POST['pagenest-listing-nonce'] = 'valid';
pagenest_save_category_listing(7);
category_check(
    $writes === 1 &&
        $term_meta[7]['_pagenest_listing'] === ['layout' => 'grid-2', 'sidebar' => 'hide'] &&
        $term_meta[7]['foreign'] === ['keep' => true],
    'valid save touches only display metadata',
);
ob_start();
pagenest_category_listing_fields(get_term(7, 'category'));
$html = ob_get_clean();
category_check(
    str_contains($html, 'name="pagenest_listing[layout]"') &&
        str_contains($html, 'name="pagenest_listing[sidebar]"') &&
        str_contains($html, 'pagenest-listing-nonce'),
    'native edit fields and nonce rendered',
);
$_POST['pagenest_listing'] = ['layout' => 'inherit', 'sidebar' => 'inherit'];
pagenest_save_category_listing(7);
category_check(
    $writes === 2 && $term_meta === [7 => ['foreign' => ['keep' => true]]],
    'double inherit deletes only own override',
);
echo "Category listing precedence, edit fields and safe save contracts passed\n";
