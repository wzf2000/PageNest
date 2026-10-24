<?php
/** Read-only native Font Library integration. No uploads, downloads or remote font sources. */
defined('ABSPATH') || exit();
function pagenest_fonts_available(): bool
{
    return function_exists('wp_get_font_dir') &&
        post_type_exists('wp_font_family') &&
        post_type_exists('wp_font_face');
}
function pagenest_font_id($value): int
{
    return (is_int($value) || is_string($value)) && preg_match('/^[0-9]+$/D', (string) $value)
        ? absint($value)
        : 0;
}
function pagenest_font_file($source): string
{
    if (
        !pagenest_fonts_available() ||
        !is_string($source) ||
        preg_match('/[\x00-\x20\x7f"\x27<>\\\\]/', $source)
    ) {
        return '';
    }
    $dir = wp_get_font_dir();
    if (!empty($dir['error']) || empty($dir['basedir']) || empty($dir['baseurl'])) {
        return '';
    }
    $base_url = rtrim($dir['baseurl'], '/');
    $base_path = realpath($dir['basedir']);
    $url = wp_parse_url($base_url);
    $site = wp_parse_url(home_url('/'));
    if (
        !$base_path ||
        !in_array($url['scheme'] ?? '', ['http', 'https'], true) ||
        ($url['scheme'] ?? '') !== ($site['scheme'] ?? '') ||
        ($url['host'] ?? '') !== ($site['host'] ?? '') ||
        ($url['port'] ?? null) !== ($site['port'] ?? null) ||
        isset($url['query']) ||
        isset($url['fragment']) ||
        isset($url['user'])
    ) {
        return '';
    }
    if (str_starts_with($source, $base_url . '/')) {
        $relative = rawurldecode(substr($source, strlen($base_url) + 1));
        if (str_contains($relative, '?') || str_contains($relative, '#')) {
            return '';
        }
        $path = $base_path . '/' . $relative;
    } elseif (str_starts_with($source, rtrim($dir['basedir'], '/') . '/')) {
        $path = $source;
        $relative = substr($source, strlen(rtrim($dir['basedir'], '/')) + 1);
    } else {
        return '';
    }
    if (
        preg_match('/[\x00-\x20\x7f"\x27<>\\\\]/', $relative) ||
        array_intersect(explode('/', $relative), ['', '.', '..'])
    ) {
        return '';
    }
    $real = realpath($path);
    if (
        !$real ||
        !str_starts_with($real, $base_path . DIRECTORY_SEPARATOR) ||
        !is_file($real) ||
        !is_readable($real)
    ) {
        return '';
    }
    $type = strtolower(pathinfo($real, PATHINFO_EXTENSION));
    $magic = [
        'ttf' => ["\x00\x01\x00\x00", 'true'],
        'otf' => ['OTTO'],
        'woff' => ['wOFF'],
        'woff2' => ['wOF2'],
    ];
    if (
        !isset($magic[$type]) ||
        !in_array(file_get_contents($real, false, null, 0, 4), $magic[$type], true)
    ) {
        return '';
    }
    return $base_url . '/' . implode('/', array_map('rawurlencode', explode('/', $relative)));
}
function pagenest_font_family(int $id): array
{
    if (!$id || !pagenest_fonts_available()) {
        return [];
    }
    $family = get_post($id);
    if (!$family || $family->post_type !== 'wp_font_family' || $family->post_status !== 'publish') {
        return [];
    }
    $faces = get_posts([
        'post_type' => 'wp_font_face',
        'post_status' => 'publish',
        'post_parent' => $id,
        'numberposts' => 100,
        'orderby' => 'ID',
        'order' => 'ASC',
        'suppress_filters' => true,
    ]);
    $rules = [];
    foreach ($faces as $face) {
        if (
            $face->post_type !== 'wp_font_face' ||
            $face->post_status !== 'publish' ||
            (int) $face->post_parent !== $id ||
            strlen($face->post_content) > 16384
        ) {
            continue;
        }
        $data = json_decode($face->post_content, true, 8);
        if (
            !is_array($data) ||
            !in_array($data['fontStyle'] ?? 'normal', ['normal', 'italic', 'oblique'], true)
        ) {
            continue;
        }
        $weight = $data['fontWeight'] ?? '400';
        $weight = $weight === 'normal' ? '400' : ($weight === 'bold' ? '700' : $weight);
        if (
            (!is_string($weight) && !is_int($weight)) ||
            !preg_match(
                '/^(?:[1-9][0-9]{0,2}|1000)(?: (?:[1-9][0-9]{0,2}|1000))?$/D',
                (string) $weight,
            )
        ) {
            continue;
        }
        $range = explode(' ', (string) $weight);
        if (count($range) === 2 && (int) $range[0] > (int) $range[1]) {
            continue;
        }
        $sources = $data['src'] ?? [];
        $sources = is_string($sources) ? [$sources] : $sources;
        if (!is_array($sources) || !$sources || count($sources) > 8) {
            continue;
        }
        $urls = array_map('pagenest_font_file', $sources);
        if (in_array('', $urls, true)) {
            continue;
        }
        $unicode = $data['unicodeRange'] ?? '';
        if (
            !is_string($unicode) ||
            ($unicode !== '' &&
                !preg_match(
                    '/^U\+[A-Fa-f0-9?]{1,6}(?:-[A-Fa-f0-9]{1,6})?(?:,\s*U\+[A-Fa-f0-9?]{1,6}(?:-[A-Fa-f0-9]{1,6})?)*$/D',
                    $unicode,
                ))
        ) {
            continue;
        }
        $rules[] =
            '@font-face{font-family:"PageNestLibrary' .
            $id .
            '";src:' .
            implode(',', array_map(static fn($url) => 'url("' . $url . '")', $urls)) .
            ';font-style:' .
            ($data['fontStyle'] ?? 'normal') .
            ';font-weight:' .
            $weight .
            ';font-display:swap;' .
            ($unicode !== '' ? 'unicode-range:' . $unicode . ';' : '') .
            '}';
    }
    return $rules
        ? [
            'label' => wp_strip_all_tags($family->post_title),
            'css' => implode('', array_unique($rules)),
        ]
        : [];
}
function pagenest_fonts_css(): string
{
    $css = '';
    $selected = [];
    $variables = [];
    foreach (['body', 'heading'] as $role) {
        $id = pagenest_font_id(get_theme_mod('pagenest_font_' . $role, 0));
        $family = pagenest_font_family($id);
        if (!$family) {
            continue;
        }
        $selected[$id] = $family['css'];
        if ($role === 'body') {
            $variables[] =
                '--pagenest-body-font:"PageNestLibrary' .
                $id .
                '",-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif';
            $variables[] =
                '--pagenest-reading-font:"PageNestLibrary' .
                $id .
                '",-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Hiragino Sans GB","Microsoft YaHei","Noto Sans CJK SC",sans-serif';
        } else {
            $variables[] =
                '--pagenest-heading-font:"PageNestLibrary' .
                $id .
                '",Georgia,"Noto Serif CJK SC","Songti SC",SimSun,serif';
        }
    }
    if ($variables) {
        $css = implode('', $selected) . ':root{' . implode(';', $variables) . '}';
    }
    return $css;
}
function pagenest_font_library_url(): string
{
    return pagenest_fonts_available() && is_file(ABSPATH . 'wp-admin/font-library.php')
        ? admin_url('font-library.php')
        : '';
}
add_action('customize_register', static function ($customizer): void {
    $choices = [0 => __('System font stack', 'pagenest')];
    if (pagenest_fonts_available()) {
        $families = get_posts([
            'post_type' => 'wp_font_family',
            'post_status' => 'publish',
            'numberposts' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
            'suppress_filters' => true,
        ]);
        foreach (['body', 'heading'] as $role) {
            $id = pagenest_font_id(get_theme_mod('pagenest_font_' . $role, 0));
            if ($id && ($family = get_post($id))) {
                $families[] = $family;
            }
        }
        foreach ($families as $family) {
            $valid = pagenest_font_family((int) $family->ID);
            if ($valid) {
                $choices[$family->ID] = $valid['label'];
            }
        }
    }
    $description = pagenest_fonts_available()
        ? __(
            'Choose an installed local Font Library family. Missing files fall back to system fonts. Install and manage fonts in WordPress; the theme never downloads fonts.',
            'pagenest',
        )
        : __(
            'This WordPress version does not provide the native Font Library. System font stacks remain available.',
            'pagenest',
        );
    foreach (
        ['body' => __('Body font', 'pagenest'), 'heading' => __('Heading font', 'pagenest')]
        as $role => $label
    ) {
        $key = 'pagenest_font_' . $role;
        $customizer->add_setting($key, [
            'default' => 0,
            'sanitize_callback' => static function ($value) {
                $id = pagenest_font_id($value);
                return $id && pagenest_font_family($id) ? $id : 0;
            },
            'capability' => 'edit_theme_options',
            'transport' => 'refresh',
        ]);
        $customizer->add_control($key, [
            'section' => 'pagenest_appearance',
            'label' => $label,
            'type' => 'select',
            'choices' => $choices,
            'description' =>
                $role === 'body'
                    ? $description .
                        (($url = pagenest_font_library_url())
                            ? ' <a href="' .
                                esc_url($url) .
                                '">' .
                                esc_html__('Manage installed fonts', 'pagenest') .
                                '</a>'
                            : '')
                    : '',
        ]);
    }
});
