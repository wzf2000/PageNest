<?php
/** Static serialization stand-ins; native WordPress parses and serializes integration samples. */
function pagenest_product_supports_details()
{
    return true;
}
function wp_kses_post($html)
{
    return $html;
}
function is_preview()
{
    return false;
}
function is_feed()
{
    return false;
}
function is_embed()
{
    return false;
}
function is_front_page()
{
    return false;
}
function is_wp_error($value)
{
    return false;
}
function parse_blocks($content)
{
    return $GLOBALS['fixture_region_blocks'];
}
function serialize_blocks($blocks)
{
    $html = '';
    foreach ($blocks as $block) {
        $child = 0;
        foreach ($block['innerContent'] ?? [$block['innerHTML'] ?? ''] as $part) {
            $html .= $part === null ? serialize_blocks([$block['innerBlocks'][$child++]]) : $part;
        }
    }
    return $html;
}
$fixture_region_html =
    '<div class="wp-block-columns"><div class="wp-block-column"><h2>继续探索这个专题</h2><p>介绍后续阅读与公开资源，保留字面记号 [B,T,D] 和 [example]。</p><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link" href="#pagenest-main">继续阅读</a></div></div></div><div class="wp-block-column"><figure class="wp-block-image"><img src="/assets/fixture-cover.svg" alt="专题示意"></figure><details class="wp-block-details"><summary>如何使用这份导览？</summary><p>选择一个主题，按自己的节奏继续阅读。</p></details></div></div>';
$fixture_region_blocks = [
    [
        'blockName' => 'core/group',
        'attrs' => [],
        'innerHTML' => $fixture_region_html,
        'innerBlocks' => [],
    ],
];
// These trees mirror parse_blocks output for native editor-saved wrappers and nested children.
$region_layout_sample = static function ($class, $layout, $children) {
    $html = '<div class="wp-block-group ' . $class . '">';
    return [
        'blockName' => 'core/group',
        'attrs' => ['layout' => $layout],
        'innerHTML' => $html . '</div>',
        'innerContent' => array_merge([$html], array_fill(0, count($children), null), ['</div>']),
        'innerBlocks' => array_map(
            static fn($text) => [
                'blockName' => 'core/paragraph',
                'attrs' => [],
                'innerHTML' => '<p>' . $text . '</p>',
                'innerBlocks' => [],
            ],
            $children,
        ),
    ];
};
$fixture_region_blocks[] = $region_layout_sample(
    'fixture-row',
    ['type' => 'flex', 'justifyContent' => 'center'],
    ['Row A', 'Row B'],
);
$fixture_region_blocks[] = $region_layout_sample(
    'fixture-stack',
    ['type' => 'flex', 'orientation' => 'vertical', 'justifyContent' => 'center'],
    ['Stack A', 'Stack B'],
);
$fixture_region_blocks[] = $region_layout_sample(
    'fixture-grid',
    ['type' => 'grid', 'columnCount' => 3],
    ['Grid A', 'Grid B', 'Grid C'],
);
$fixture_region_blocks[] = $region_layout_sample(
    'fixture-grid-auto',
    ['type' => 'grid'],
    ['Auto A', 'Auto B', 'Auto C'],
);
$buttons = $region_layout_sample(
    'fixture-buttons',
    ['type' => 'flex', 'justifyContent' => 'center'],
    ['Button A', 'Button B'],
);
$buttons['blockName'] = 'core/buttons';
$buttons['innerBlocks'] = array_map(
    static fn($label) => [
        'blockName' => 'core/button',
        'attrs' => [],
        'innerBlocks' => [],
        'innerHTML' =>
            '<div class="wp-block-button"><a class="wp-block-button__link" href="#pagenest-main">' .
            $label .
            '</a></div>',
    ],
    ['Button A', 'Button B'],
);
$fixture_region_blocks[] = $buttons;
$posts[3001] = new WP_Post(3001, 'publish', 'page');
$posts[3001]->post_parent = 0;
$posts[3001]->post_content = 'Static sample supplied as saved block markup.';
$mods = [
    'pagenest_header_layout' => 'two-row',
    'pagenest_footer_layout' => 'stacked',
    'pagenest_region_after_header_source' => 3001,
    'pagenest_region_after_header_scope' => 'all',
    'pagenest_region_before_footer_source' => 3001,
    'pagenest_region_before_footer_scope' => 'all',
];
$fixture_footer_menu = true;
$fixture_region_singular = true;
$rendered['regions-article.html'] = render('singular.php');
$fixture_region_singular = false;
$rendered['regions-list.html'] = render('index.php');
// Distinct generic families exercise variable wiring without a platform font dependency.
$rendered['regions-heading-font.html'] = str_replace(
    '</head>',
    '<style>:root{--pagenest-heading-font:monospace}body.pagenest-site{font-family:serif}</style></head>',
    $rendered['regions-list.html'],
);
$mods['pagenest_color_scheme'] = 'dark';
$rendered['regions-dark.html'] = render('index.php');
$mods = [];
$fixture_footer_menu = false;
unset($posts[3001]);
