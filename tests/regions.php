<?php
/** Static region contracts use synthetic parsed block trees, never a database. */
define('ABSPATH', __DIR__);
$hooks = $mods = $posts = [];
$flags = [
    'singular' => false,
    'front' => false,
    'home' => false,
    'archive' => false,
    'preview' => false,
    '404' => false,
    'feed' => false,
    'embed' => false,
];
$blocks = [];
$details = true;
$current = 2;
$serialized = $parsed = $cleaned = 0;
class WP_Post
{
    public $ID,
        $post_type = 'page',
        $post_status = 'publish',
        $post_password = '',
        $post_parent = 0,
        $post_content = 'saved content';
    public function __construct($id)
    {
        $this->ID = $id;
    }
}
class WP_Error
{
    public $errors = [];
    public function __construct($code = '', $message = '')
    {
        if ($code) {
            $this->add($code, $message);
        }
    }
    public function add($code, $message)
    {
        $this->errors[$code] = $message;
    }
    public function get_error_code()
    {
        return array_key_first($this->errors);
    }
    public function get_error_message()
    {
        return reset($this->errors);
    }
}
function is_wp_error($value)
{
    return $value instanceof WP_Error;
}
function __($text, $domain)
{
    return $text;
}
function esc_attr($text)
{
    return htmlspecialchars($text, ENT_QUOTES);
}
function absint($value)
{
    return abs((int) $value);
}
function add_action($name, $callback)
{
    $GLOBALS['hooks'][$name][] = $callback;
}
function get_post($id)
{
    return $GLOBALS['posts'][$id] ?? null;
}
function get_theme_mod($key, $default)
{
    return $GLOBALS['mods'][$key] ?? $default;
}
function get_queried_object_id()
{
    return $GLOBALS['current'];
}
function is_preview()
{
    return $GLOBALS['flags']['preview'];
}
function is_404()
{
    return $GLOBALS['flags']['404'];
}
function is_feed()
{
    return $GLOBALS['flags']['feed'];
}
function is_embed()
{
    return $GLOBALS['flags']['embed'];
}
function is_front_page()
{
    return $GLOBALS['flags']['front'];
}
function is_home()
{
    return $GLOBALS['flags']['home'];
}
function is_archive()
{
    return $GLOBALS['flags']['archive'];
}
function is_singular($type = null)
{
    return $GLOBALS['flags']['singular'] &&
        (!$type || get_post(get_queried_object_id())->post_type === $type);
}
function pagenest_product_supports_details()
{
    return $GLOBALS['details'];
}
function parse_blocks($content)
{
    $GLOBALS['parsed']++;
    return $GLOBALS['blocks'];
}
function serialize_blocks($blocks)
{
    $GLOBALS['serialized']++;
    $html = '';
    foreach ($blocks as $block) {
        $child = 0;
        foreach ($block['innerContent'] ?? [$block['innerHTML'] ?? ''] as $part) {
            $html .= $part === null ? serialize_blocks([$block['innerBlocks'][$child++]]) : $part;
        }
    }
    return $html;
}
function wp_kses_post($html)
{
    $GLOBALS['cleaned']++;
    return $html;
}
function render_block($block)
{
    throw new RuntimeException('Rendering must never run');
}
function do_blocks($content)
{
    throw new RuntimeException('Rendering must never run');
}
function do_shortcode($content)
{
    throw new RuntimeException('Shortcodes must never run');
}
function apply_filters(...$args)
{
    throw new RuntimeException('Content filters must never run');
}
function region_check($value, $message)
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}
require dirname(__DIR__) . '/regions.php';
$posts[1] = new WP_Post(1);
$posts[2] = new WP_Post(2);
$blocks = [
    [
        'blockName' => 'core/paragraph',
        'attrs' => [],
        'innerHTML' => '<p>Static [B,T,D] and [literal] text</p>',
        'innerBlocks' => [],
    ],
];
region_check(
    pagenest_region_source(1) === '<p>Static [B,T,D] and [literal] text</p>' &&
        $serialized === 1 &&
        $cleaned === 1,
    'only validated serialization and KSES run; literal brackets remain',
);
$original_blocks = $blocks;
$layout_block = static function ($name, $layout) {
    return [
        'blockName' => $name,
        'attrs' => ['layout' => $layout],
        'innerHTML' => '<div class="wp-block-group"></div>',
        'innerContent' => ['<div class="wp-block-group">', null, '</div>'],
        'innerBlocks' => [
            [
                'blockName' => 'core/paragraph',
                'attrs' => [],
                'innerHTML' => '<p>Saved child</p>',
                'innerBlocks' => [],
            ],
        ],
    ];
};
foreach (
    [
        ['type' => 'flex', 'justifyContent' => 'center'],
        [
            'type' => 'flex',
            'orientation' => 'vertical',
            'justifyContent' => 'right',
            'verticalAlignment' => 'space-between',
        ],
        ['type' => 'flex', 'flexWrap' => 'nowrap'],
        ['type' => 'grid'],
        ['type' => 'grid', 'columnCount' => 3],
    ]
    as $layout
) {
    $blocks = [$layout_block('core/group', $layout)];
    $output = pagenest_region_source(1);
    region_check(
        is_string($output) &&
            str_contains($output, 'data-pagenest-layout="' . $layout['type'] . '"') &&
            str_contains($output, '<p>Saved child</p>'),
        'native innerContent wrapper converted; saved children preserved',
    );
    region_check(
        !str_contains($blocks[0]['innerContent'][0], 'data-pagenest-'),
        'parsed source is not mutated',
    );
}
$blocks = [$layout_block('core/buttons', ['type' => 'flex', 'justifyContent' => 'center'])];
region_check(
    str_contains(pagenest_region_source(1), 'data-pagenest-justify="center"'),
    'native buttons alignment is converted',
);
foreach (
    [
        null,
        'flex',
        ['type' => null],
        ['type' => 'flex', 'justifyContent' => null],
        ['type' => 'flex', 'justifyContent' => 'center;display:none'],
        ['type' => 'flex', 'orientation' => []],
        ['type' => 'flex', 'flexWrap' => 'invalid'],
        ['type' => 'flex', 'verticalAlignment' => 'space-between'],
        ['type' => 'grid', 'columnCount' => '3'],
        ['type' => 'grid', 'columnCount' => 0],
        ['type' => 'grid', 'columnCount' => 13],
        ['type' => 'grid', 'minimumColumnWidth' => '24rem'],
        ['type' => 'grid', 'autoFit' => false],
        ['type' => 'constrained', 'contentSize' => '40rem'],
        ['type' => 'flex', 'viewport' => ['mobile' => []]],
        ['type' => 'unknown'],
    ]
    as $layout
) {
    $blocks = [$layout_block('core/group', $layout)];
    $before = $serialized;
    region_check(
        is_wp_error(pagenest_region_source(1)) && $serialized === $before,
        'invalid or unsupported native layout rejected before serialization',
    );
}
$blocks = [$layout_block('core/group', ['type' => 'flex'])];
$blocks[0]['innerBlocks'][0]['attrs']['style']['layout'] = ['columnSpan' => 2];
region_check(is_wp_error(pagenest_region_source(1)), 'unsupported child positioning rejected');
$blocks = $original_blocks;
foreach (
    [
        'core/html',
        'core/shortcode',
        'core/query',
        'core/embed',
        'core/block',
        'plugin/example',
        null,
    ]
    as $name
) {
    $blocks[0]['blockName'] = $name;
    $before = $serialized;
    region_check(
        is_wp_error(pagenest_region_source(1)) && $serialized === $before,
        'unsafe blocks rejected before serialization',
    );
}
$blocks[0]['blockName'] = 'core/paragraph';
foreach (
    [
        ['metadata' => ['bindings' => []]],
        ['style' => ['nested' => ['metadata' => ['bindings' => ['text' => []]]]]],
    ]
    as $attrs
) {
    $blocks[0]['attrs'] = $attrs;
    region_check(is_wp_error(pagenest_region_source(1)), 'nested bindings rejected');
}
$blocks[0]['attrs'] = [];
foreach (
    [
        '<h2 id="body-heading">Heading</h2>',
        '<p title="a>b" id="body-heading">Text</p>',
        '<p/ID=body-heading>Text</p>',
    ]
    as $html
) {
    $blocks[0]['innerHTML'] = $html;
    region_check(
        is_wp_error(pagenest_region_source(1)),
        'saved HTML IDs rejected, including quoted greater-than and slash separator',
    );
}
$blocks[0]['innerHTML'] =
    '<p title="id=literal">Literal id=1; <a href="#body-heading">a body link</a></p>';
region_check(
    !is_wp_error(pagenest_region_source(1)),
    'ordinary text, attribute values and body fragment links remain supported',
);
$blocks[0]['attrs'] = ['anchor' => 'body-heading'];
region_check(
    is_wp_error(pagenest_region_source(1)),
    'anchor attribute rejected before serialization',
);
$mods = [
    'pagenest_region_after_header_scope' => 'all',
    'pagenest_region_after_header_source' => 1,
    'pagenest_region_before_footer_scope' => 'all',
    'pagenest_region_before_footer_source' => 1,
];
region_check(
    pagenest_region_content('after_header') === '' &&
        pagenest_region_content('before_footer') === '',
    'source anchors cannot collide between slots or with body IDs',
);
$mods = [];
$blocks[0]['innerHTML'] = '<p>Static [B,T,D] and [literal] text</p>';
$blocks[0]['attrs'] = [];
$blocks[0]['innerBlocks'] = array_fill(0, 201, $blocks[0]);
region_check(is_wp_error(pagenest_region_source(1)), 'node budget enforced');
$blocks[0]['innerBlocks'] = [];
$leaf = $blocks[0];
for ($i = 0; $i < 18; $i++) {
    $leaf = [
        'blockName' => 'core/group',
        'attrs' => [],
        'innerHTML' => '',
        'innerBlocks' => [$leaf],
    ];
}
$blocks = [$leaf];
region_check(is_wp_error(pagenest_region_source(1)), 'depth budget enforced');
$blocks = [
    [
        'blockName' => 'core/details',
        'innerHTML' => '<details><summary>Question</summary></details>',
        'innerBlocks' => [],
    ],
];
$details = false;
region_check(is_wp_error(pagenest_region_source(1)), 'unavailable core details rejected');
$details = true;
region_check(!is_wp_error(pagenest_region_source(1)), 'available static details accepted');
$posts[1]->post_content = str_repeat('x', 65537);
$before = $parsed;
region_check(
    is_wp_error(pagenest_region_source(1)) && $parsed === $before,
    'source bytes bounded before parsing',
);
$posts[1]->post_content = 'saved content';
$posts[3] = new WP_Post(3);
$posts[1]->post_parent = 3;
foreach (
    ['post_status' => 'private', 'post_password' => 'secret', 'post_type' => 'post']
    as $field => $value
) {
    $old = $posts[3]->$field;
    $posts[3]->$field = $value;
    region_check(
        is_wp_error(pagenest_region_source(1)),
        'invalid ancestor rejected regardless of viewer',
    );
    $posts[3]->$field = $old;
}
$posts[3]->post_parent = 1;
region_check(is_wp_error(pagenest_region_source(1)), 'ancestor cycle rejected');
$posts[3]->post_parent = 99;
region_check(is_wp_error(pagenest_region_source(1)), 'missing ancestor rejected');
$posts[1]->post_parent = 0;
foreach (['draft', 'private', 'trash'] as $status) {
    $posts[1]->post_status = $status;
    region_check(is_wp_error(pagenest_region_source(1)), 'unpublished source rejected');
}
$posts[1]->post_status = 'publish';
region_check(pagenest_region_content('after_header') === '', 'regions default off');
$mods = ['pagenest_region_after_header_scope' => 'all', 'pagenest_region_after_header_source' => 1];
region_check(
    pagenest_region_content('after_header') !== '',
    'all includes public listings including search',
);
region_check(pagenest_region_content('before_footer') === '', 'slots remain independent');
foreach (['preview', '404', 'feed', 'embed'] as $flag) {
    $flags[$flag] = true;
    region_check(pagenest_region_content('after_header') === '', 'excluded request context');
    $flags[$flag] = false;
}
foreach (['front' => 'front', 'blog' => 'home', 'archive' => 'archive'] as $scope => $flag) {
    $mods['pagenest_region_after_header_scope'] = $scope;
    region_check(
        pagenest_region_content('after_header') === '',
        'scope does not leak to other listings',
    );
    $flags[$flag] = true;
    region_check(pagenest_region_content('after_header') !== '', 'matching listing scope');
    $flags[$flag] = false;
}
$flags['singular'] = true;
foreach (['post' => 'posts', 'page' => 'pages'] as $type => $scope) {
    $posts[2]->post_type = $type;
    $mods['pagenest_region_after_header_scope'] = $scope;
    region_check(pagenest_region_content('after_header') !== '', 'matching singular scope');
    foreach (['post_status' => 'private', 'post_password' => 'secret'] as $field => $value) {
        $old = $posts[2]->$field;
        $posts[2]->$field = $value;
        region_check(
            pagenest_region_content('after_header') === '',
            'private or password current request excluded',
        );
        $posts[2]->$field = $old;
    }
}
$current = 1;
region_check(pagenest_region_content('after_header') === '', 'source page does not repeat itself');
region_check(
    pagenest_region_validate_source(new WP_Error(), 0)->errors === [],
    'source can be cleared even with region off',
);
region_check(
    pagenest_region_validate_source(new WP_Error(), 999)->errors !== [],
    'source validation reports unavailable page',
);
region_check(
    pagenest_region_scope([]) === 'off' &&
        pagenest_header_layout('bad') === 'standard' &&
        pagenest_footer_layout([]) === 'standard',
    'invalid enums preserve safe defaults',
);
echo "Public region visibility, static block budgets, binding rejection and scope contracts passed\n";
