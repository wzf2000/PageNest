<?php
/** Public static page regions; no content rendering callbacks or role conditions. */
defined('ABSPATH') || exit();
function pagenest_header_layout($value): string
{
    return in_array($value, ['standard', 'two-row'], true) ? $value : 'standard';
}
function pagenest_footer_layout($value): string
{
    return in_array($value, ['standard', 'stacked'], true) ? $value : 'standard';
}
function pagenest_region_scope($value): string
{
    return in_array($value, ['off', 'all', 'front', 'blog', 'archive', 'posts', 'pages'], true)
        ? $value
        : 'off';
}
function pagenest_region_id($value): int
{
    return (is_int($value) || is_string($value)) && preg_match('/^[0-9]+$/D', (string) $value)
        ? absint($value)
        : 0;
}
function pagenest_region_page($id)
{
    $source = get_post($id);
    $post = $source;
    $seen = [];
    while ($post) {
        if (
            !($post instanceof WP_Post) ||
            $post->post_type !== 'page' ||
            $post->post_status !== 'publish' ||
            $post->post_password !== '' ||
            isset($seen[$post->ID]) ||
            count($seen) >= 32
        ) {
            return new WP_Error(
                'region_visibility',
                __(
                    'Choose a published page without a password whose parent pages are also public.',
                    'pagenest',
                ),
            );
        }
        $seen[$post->ID] = true;
        if (!$post->post_parent) {
            return $source;
        }
        $post = get_post($post->post_parent);
    }
    return new WP_Error(
        'region_page',
        __('The source page or one of its parents is unavailable.', 'pagenest'),
    );
}
function pagenest_region_has_bindings($value, int $depth = 0): bool
{
    if ($depth > 16) {
        return true;
    }
    if (!is_array($value)) {
        return false;
    }
    foreach ($value as $key => $child) {
        if ($key === 'bindings' || pagenest_region_has_bindings($child, $depth + 1)) {
            return true;
        }
    }
    return false;
}
/** Convert only finite native layout choices; never generate CSS from saved strings. */
function pagenest_region_layout(array &$block)
{
    $name = $block['blockName'] ?? '';
    if (!in_array($name, ['core/group', 'core/buttons'], true)) {
        return true;
    }
    $attrs = $block['attrs'] ?? [];
    $layout = array_key_exists('layout', $attrs) ? $attrs['layout'] : [];
    $error = static function () {
        return new WP_Error(
            'region_layout',
            __(
                'This region layout is not supported. Use default groups, rows or stacks with alignment and wrapping, or grids with automatic columns or 1–12 columns. Custom widths, child positioning and responsive overrides are not supported.',
                'pagenest',
            ),
        );
    };
    if (!is_array($layout) || in_array(null, $layout, true)) {
        return $error();
    }
    $type = $layout['type'] ?? ($name === 'core/buttons' ? 'flex' : 'default');
    if (!is_string($type)) {
        return $error();
    }
    $allowed = match ($type) {
        'default', 'constrained' => ['type'],
        'flex' => ['type', 'orientation', 'justifyContent', 'verticalAlignment', 'flexWrap'],
        'grid' => ['type', 'columnCount'],
        default => [],
    };
    if (!$allowed || array_diff(array_keys($layout), $allowed)) {
        return $error();
    }
    // Flow/constrained groups retain their saved markup and the region's existing width.
    if (in_array($type, ['default', 'constrained'], true)) {
        return true;
    }
    $attributes = ['layout' => $type];
    if ($type === 'flex') {
        $orientation = $layout['orientation'] ?? 'horizontal';
        $justify = $layout['justifyContent'] ?? 'left';
        $align = $layout['verticalAlignment'] ?? ($orientation === 'vertical' ? 'top' : 'center');
        $wrap = $layout['flexWrap'] ?? 'wrap';
        $justifications = [
            'left',
            'center',
            'right',
            $orientation === 'vertical' ? 'stretch' : 'space-between',
        ];
        $alignments = [
            'top',
            'center',
            'bottom',
            $orientation === 'vertical' ? 'space-between' : 'stretch',
        ];
        if (
            !in_array($orientation, ['horizontal', 'vertical'], true) ||
            !in_array($justify, $justifications, true) ||
            !in_array($align, $alignments, true) ||
            !in_array($wrap, ['wrap', 'nowrap'], true)
        ) {
            return $error();
        }
        $attributes += [
            'orientation' => $orientation,
            'justify' => $justify,
            'align' => $align,
            'wrap' => $wrap,
        ];
    } else {
        $count = $layout['columnCount'] ?? 0;
        if (
            !is_int($count) ||
            $count < 0 ||
            $count > 12 ||
            (array_key_exists('columnCount', $layout) && $count === 0)
        ) {
            return $error();
        }
        $attributes['columns'] = $count ? (string) $count : 'auto';
    }
    $saved = '';
    foreach ($attributes as $key => $value) {
        $saved .= ' data-pagenest-' . $key . '="' . $value . '"';
    }
    // Native Group supports semantic wrappers as well as div. Only touch its first opening tag.
    $prefix = $block['innerContent'][0] ?? ($block['innerHTML'] ?? '');
    if (
        !is_string($prefix) ||
        !preg_match('/^\s*<(?:div|section|article|main|header|footer|aside)\b/i', $prefix) ||
        preg_match('/\bdata-pagenest-/i', $prefix)
    ) {
        return $error();
    }
    $convert = static fn($html) => preg_replace(
        '/^(\s*<[a-z][a-z0-9-]*)(?=[\s>])/i',
        '$1' . $saved,
        $html,
        1,
    );
    $block['innerHTML'] = $convert($block['innerHTML'] ?? '');
    if (isset($block['innerContent'][0])) {
        $block['innerContent'][0] = $convert($block['innerContent'][0]);
    }
    return true;
}
function pagenest_region_validate_blocks(array &$blocks, int &$nodes, int $depth = 0)
{
    if ($depth > 16) {
        return new WP_Error(
            'region_depth',
            __('The source page has too many nested blocks. Use at most 16 levels.', 'pagenest'),
        );
    }
    $allowed = [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/list-item',
        'core/group',
        'core/columns',
        'core/column',
        'core/buttons',
        'core/button',
        'core/image',
        'core/separator',
        'core/spacer',
    ];
    if (pagenest_product_supports_details()) {
        $allowed[] = 'core/details';
    }
    foreach ($blocks as &$block) {
        if (++$nodes > 200) {
            return new WP_Error(
                'region_nodes',
                __('The source page has too many blocks. Use at most 200 blocks.', 'pagenest'),
            );
        }
        $name = $block['blockName'] ?? null;
        if (
            $name === null &&
            trim($block['innerHTML'] ?? '') === '' &&
            empty($block['innerBlocks'])
        ) {
            continue;
        }
        if (!in_array($name, $allowed, true)) {
            return new WP_Error(
                'region_block',
                __(
                    'Use only static paragraphs, headings, lists, groups, columns, buttons, images, separators, spacers and supported Details blocks. HTML, shortcodes, reusable patterns and dynamic blocks are not supported.',
                    'pagenest',
                ),
            );
        }
        if (
            ($block['attrs']['anchor'] ?? '') !== '' ||
            preg_match(
                '/<(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*[\s\/]id\s*=/i',
                $block['innerHTML'] ?? '',
            )
        ) {
            return new WP_Error(
                'region_anchor',
                __(
                    'Custom HTML anchors are not supported in content regions. Remove the block anchors and HTML id attributes from the source page.',
                    'pagenest',
                ),
            );
        }
        if (pagenest_region_has_bindings($block['attrs'] ?? [])) {
            return new WP_Error(
                'region_binding',
                __('Data bindings are not supported in public content regions.', 'pagenest'),
            );
        }
        if (!empty($block['attrs']['style']['layout'])) {
            return new WP_Error(
                'region_layout',
                __('Child positioning is not supported in public content regions.', 'pagenest'),
            );
        }
        $valid = pagenest_region_layout($block);
        if (is_wp_error($valid)) {
            return $valid;
        }
        $block['innerBlocks'] ??= [];
        $valid = pagenest_region_validate_blocks($block['innerBlocks'], $nodes, $depth + 1);
        if (is_wp_error($valid)) {
            return $valid;
        }
    }
    return true;
}
function pagenest_region_source(int $id)
{
    if ($id === 0) {
        return '';
    }
    $source = pagenest_region_page($id);
    if (is_wp_error($source)) {
        return $source;
    }
    if (strlen($source->post_content) > 65536) {
        return new WP_Error(
            'region_size',
            __('The source page is too large. Keep its saved content within 64 KiB.', 'pagenest'),
        );
    }
    $blocks = parse_blocks($source->post_content);
    $nodes = 0;
    $valid = pagenest_region_validate_blocks($blocks, $nodes);
    return is_wp_error($valid) ? $valid : wp_kses_post(serialize_blocks($blocks));
}
function pagenest_region_validate_source($validity, $value)
{
    $id = pagenest_region_id($value);
    if (!$id && !in_array($value, [0, '0'], true)) {
        $validity->add('region_id', __('Choose a source page or clear the selection.', 'pagenest'));
        return $validity;
    }
    $result = pagenest_region_source($id);
    if (is_wp_error($result)) {
        $validity->add($result->get_error_code(), $result->get_error_message());
    }
    return $validity;
}
function pagenest_region_content(string $slot): string
{
    if (!in_array($slot, ['after_header', 'before_footer'], true)) {
        return '';
    }
    $scope = pagenest_region_scope(get_theme_mod('pagenest_region_' . $slot . '_scope', 'off'));
    if ($scope === 'off' || is_preview() || is_404() || is_feed() || is_embed()) {
        return '';
    }
    $singular = is_singular();
    if ($singular) {
        $post = get_post(get_queried_object_id());
        if (
            !$post ||
            !in_array($post->post_type, ['post', 'page'], true) ||
            $post->post_status !== 'publish' ||
            $post->post_password !== ''
        ) {
            return '';
        }
    }
    $matches = match ($scope) {
        'all' => true,
        'front' => is_front_page(),
        'blog' => is_home(),
        'archive' => is_archive(),
        'posts' => $singular && is_singular('post'),
        'pages' => $singular && is_singular('page'),
        default => false,
    };
    $id = pagenest_region_id(get_theme_mod('pagenest_region_' . $slot . '_source', 0));
    if (!$matches || !$id || ($singular && $id === (int) get_queried_object_id())) {
        return '';
    }
    $content = pagenest_region_source($id);
    return is_wp_error($content) ? '' : $content;
}
function pagenest_render_region(string $slot): void
{
    $html = pagenest_region_content($slot);
    // Empty saved pages and whitespace-only block comments produce no empty wrapper.
    if (trim(preg_replace('/<!--.*?-->/s', '', $html)) !== '') {
        echo '<div class="pagenest-region pagenest-region-' .
            esc_attr($slot) .
            '">' .
            $html .
            '</div>';
    }
}
function pagenest_region_admin_status(): void
{
    if (!current_user_can('edit_theme_options')) {
        return;
    }
    echo '<ul>';
    foreach (
        [
            'after_header' => __('After the header', 'pagenest'),
            'before_footer' => __('Before the footer', 'pagenest'),
        ]
        as $slot => $label
    ) {
        $scope = pagenest_region_scope(get_theme_mod('pagenest_region_' . $slot . '_scope', 'off'));
        echo '<li><strong>' . esc_html($label) . '</strong>: ';
        if ($scope === 'off') {
            echo esc_html__('Off', 'pagenest');
        } else {
            $id = pagenest_region_id(get_theme_mod('pagenest_region_' . $slot . '_source', 0));
            $result = pagenest_region_source($id);
            if (is_wp_error($result)) {
                echo esc_html__('Source unavailable', 'pagenest') .
                    ' — ' .
                    esc_html($result->get_error_message());
            } elseif ($id) {
                echo esc_html(get_post($id)->post_title) .
                    ' — ' .
                    esc_html__('Available', 'pagenest');
            } else {
                echo esc_html__('Choose a source page.', 'pagenest');
            }
            if (
                $id &&
                current_user_can('edit_post', $id) &&
                ($url = get_edit_post_link($id, 'raw'))
            ) {
                echo ' <a href="' .
                    esc_url($url) .
                    '">' .
                    esc_html__('Edit source page', 'pagenest') .
                    '</a>';
            }
        }
        echo '</li>';
    }
    echo '</ul><p><a href="' .
        esc_url(admin_url('edit.php?post_type=page')) .
        '">' .
        esc_html__('Manage source pages', 'pagenest') .
        '</a></p>';
}
add_action('customize_register', static function ($customizer): void {
    $customizer->add_section('pagenest_regions', [
        'title' => __('Header, footer and public regions', 'pagenest'),
        'panel' => 'pagenest_design',
        'description' => __(
            'Choose a public, password-free page and maintain its content in the block editor. Regions support static blocks and are visible to everyone on the selected page types.',
            'pagenest',
        ),
        'priority' => 95,
    ]);
    foreach (
        ['header' => __('Header layout', 'pagenest'), 'footer' => __('Footer layout', 'pagenest')]
        as $part => $label
    ) {
        $id = 'pagenest_' . $part . '_layout';
        $customizer->add_setting($id, [
            'default' => 'standard',
            'sanitize_callback' => 'pagenest_' . $part . '_layout',
            'capability' => 'edit_theme_options',
        ]);
        $customizer->add_control($id, [
            'section' => 'pagenest_regions',
            'label' => $label,
            'type' => 'select',
            'choices' =>
                $part === 'header'
                    ? [
                        'standard' => __('Standard', 'pagenest'),
                        'two-row' => __('Two-row header', 'pagenest'),
                    ]
                    : [
                        'standard' => __('Standard', 'pagenest'),
                        'stacked' => __('Stacked footer', 'pagenest'),
                    ],
        ]);
    }
    foreach (
        [
            'after_header' => __('After the header', 'pagenest'),
            'before_footer' => __('Before the footer', 'pagenest'),
        ]
        as $slot => $label
    ) {
        $id = 'pagenest_region_' . $slot;
        $customizer->add_setting($id . '_source', [
            'default' => 0,
            'sanitize_callback' => 'pagenest_region_id',
            'validate_callback' => 'pagenest_region_validate_source',
            'capability' => 'edit_theme_options',
        ]);
        $customizer->add_control($id . '_source', [
            'section' => 'pagenest_regions',
            'label' => sprintf(__('%s · Source page', 'pagenest'), $label),
            'type' => 'dropdown-pages',
        ]);
        $customizer->add_setting($id . '_scope', [
            'default' => 'off',
            'sanitize_callback' => 'pagenest_region_scope',
            'capability' => 'edit_theme_options',
        ]);
        $customizer->add_control($id . '_scope', [
            'section' => 'pagenest_regions',
            'label' => sprintf(__('%s · Show on', 'pagenest'), $label),
            'type' => 'select',
            'choices' => [
                'off' => __('Off', 'pagenest'),
                'all' => __('All public pages and listings', 'pagenest'),
                'front' => __('Front page', 'pagenest'),
                'blog' => __('Blog listing', 'pagenest'),
                'archive' => __('Archives', 'pagenest'),
                'posts' => __('Posts', 'pagenest'),
                'pages' => __('Pages', 'pagenest'),
            ],
        ]);
    }
});
