<?php
/** Native configuration hub, portable appearance values and opt-in block examples. */
defined('ABSPATH') || exit();

function pagenest_product_schema()
{
    $schema = ['pagenest_appearance_preset' => ['type' => 'preset']];
    foreach (['background', 'text', 'accent'] as $key) {
        $schema['pagenest_appearance_' . $key] = ['type' => 'color'];
    }
    foreach (pagenest_appearance_numbers() as $key => $definition) {
        $schema['pagenest_appearance_' . $key] = ['type' => 'number', 'definition' => $definition];
    }
    return $schema;
}

function pagenest_product_validate($data)
{
    if (
        !is_array($data) ||
        array_diff(array_keys($data), ['format', 'version', 'settings']) ||
        count($data) !== 3 ||
        ($data['format'] ?? null) !== 'pagenest-appearance' ||
        ($data['version'] ?? null) !== 1 ||
        !is_array($data['settings'] ?? null)
    ) {
        return new WP_Error('format', __('Invalid appearance settings file.', 'pagenest'));
    }
    $schema = pagenest_product_schema();
    $settings = [];
    foreach ($data['settings'] as $key => $value) {
        if (!isset($schema[$key])) {
            return new WP_Error(
                'setting',
                __('The file contains unsupported settings.', 'pagenest'),
            );
        }
        $rule = $schema[$key];
        if ($rule['type'] === 'preset') {
            if (!is_string($value) || !isset(pagenest_appearance_presets()[$value])) {
                return new WP_Error(
                    'value',
                    __('A setting value is outside the allowed range.', 'pagenest'),
                );
            }
            $settings[$key] = pagenest_sanitize_appearance_preset($value);
        } elseif ($rule['type'] === 'color') {
            if (
                !is_string($value) ||
                ($value !== '' && pagenest_sanitize_appearance_color($value) === '')
            ) {
                return new WP_Error(
                    'value',
                    __('A setting value is outside the allowed range.', 'pagenest'),
                );
            }
            $settings[$key] = $value === '' ? '' : pagenest_sanitize_appearance_color($value);
        } else {
            $definition = $rule['definition'];
            if (
                (!is_int($value) && !is_float($value)) ||
                !is_finite((float) $value) ||
                $value < $definition['min'] ||
                $value > $definition['max'] ||
                abs($value - pagenest_sanitize_appearance_number($value, $definition)) > 0.000001
            ) {
                return new WP_Error(
                    'value',
                    __('A setting value is outside the allowed range.', 'pagenest'),
                );
            }
            $settings[$key] = pagenest_sanitize_appearance_number($value, $definition);
        }
    }
    return $settings;
}

function pagenest_product_export()
{
    $mods = get_theme_mods();
    $settings = array_intersect_key(is_array($mods) ? $mods : [], pagenest_product_schema());
    $data = ['format' => 'pagenest-appearance', 'version' => 1, 'settings' => $settings];
    $validated = pagenest_product_validate($data);
    if (is_wp_error($validated)) {
        return $validated;
    }
    $data['settings'] = (object) $validated;
    return $data;
}

function pagenest_product_authorize_transfer()
{
    if (!current_user_can('edit_theme_options')) {
        wp_die(esc_html__('You do not have permission to manage theme settings.', 'pagenest'), '', [
            'response' => 403,
        ]);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        wp_die(esc_html__('Submit the form from the settings page.', 'pagenest'), '', [
            'response' => 405,
        ]);
    }
    check_admin_referer('pagenest_appearance_transfer');
}

function pagenest_product_import_json($json)
{
    if (!is_string($json) || strlen($json) > 16384) {
        return new WP_Error('size', __('The settings file is too large or invalid.', 'pagenest'));
    }
    $data = json_decode($json, true, 8);
    // Settings must be a JSON object, including when empty.
    $object = json_decode($json, false, 8);
    if (
        json_last_error() !== JSON_ERROR_NONE ||
        !is_object($object) ||
        !is_object($object->settings ?? null)
    ) {
        return new WP_Error('json', __('Invalid appearance settings file.', 'pagenest'));
    }
    $validated = pagenest_product_validate($data);
    if (is_wp_error($validated)) {
        return $validated;
    }
    $mods = get_theme_mods();
    $mods = is_array($mods) ? $mods : [];
    if ($validated !== []) {
        $merged = array_replace($mods, $validated);
        if ($merged !== $mods && !update_option('theme_mods_' . get_stylesheet(), $merged)) {
            return new WP_Error(
                'save',
                __('Settings could not be saved. Please try again.', 'pagenest'),
            );
        }
    }
    return true;
}

add_action('admin_post_pagenest_appearance_export', static function () {
    pagenest_product_authorize_transfer();
    $data = pagenest_product_export();
    if (is_wp_error($data)) {
        wp_die(esc_html($data->get_error_message()), '', ['response' => 400]);
    }
    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="pagenest-appearance-v1.json"');
    echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
});
add_action('admin_post_pagenest_appearance_import', static function () {
    pagenest_product_authorize_transfer();
    $result = pagenest_product_import_json(wp_unslash($_POST['pagenest_json'] ?? null));
    if (is_wp_error($result)) {
        wp_die(esc_html($result->get_error_message()), '', ['response' => 400]);
    }
    wp_safe_redirect(admin_url('themes.php?page=pagenest&imported=1'));
    exit();
});

function pagenest_product_diagnostics($root = null)
{
    $root = $root ?? __DIR__;
    $assets = [];
    $sources = [
        'css' => 'layout.css',
        'js' => 'navigation.js',
        'reading' => 'reading.js',
        'login' => 'login.css',
        'content' => 'content.css',
        'content_js' => 'content.js',
        'appearance' => 'appearance.css',
        'browsing' => 'browsing.css',
        'reader_preferences_css' => 'reader-preferences.css',
        'reader_preferences_js' => 'reader-preferences.js',
        'discovery_css' => 'discovery.css',
        'discovery_js' => 'discovery.js',
    ];
    try {
        $manifest = is_readable($root . '/assets/manifest.php')
            ? require $root . '/assets/manifest.php'
            : [];
    } catch (Throwable $error) {
        $manifest = [];
    }
    foreach ($sources as $key => $source) {
        $name = is_array($manifest) ? $manifest[$key] ?? '' : '';
        $stem = pathinfo($source, PATHINFO_FILENAME);
        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $valid =
            is_string($name) &&
            preg_match(
                '/^' . preg_quote($stem, '/') . '-([a-f0-9]{12})\.' . $extension . '$/D',
                $name,
                $matches,
            );
        $exists =
            $valid && is_file($root . '/assets/' . $name) && !is_link($root . '/assets/' . $name);
        $assetHash =
            $exists && is_readable($root . '/assets/' . $name)
                ? @hash_file('sha256', $root . '/assets/' . $name)
                : false;
        $sourceHash =
            is_readable($root . '/assets/' . $source) && !is_link($root . '/assets/' . $source)
                ? @hash_file('sha256', $root . '/assets/' . $source)
                : false;
        $assets[$key] =
            $assetHash &&
            $sourceHash &&
            $assetHash === $sourceHash &&
            substr($assetHash, 0, 12) === $matches[1];
    }
    return $assets;
}

function pagenest_product_page()
{
    if (!current_user_can('edit_theme_options')) {
        wp_die(esc_html__('You do not have permission to manage theme settings.', 'pagenest'), '', [
            'response' => 403,
        ]);
    }
    echo '<div class="wrap"><h1>PageNest · ' . esc_html__('Theme settings', 'pagenest') . '</h1>';
    echo '<p>' .
        esc_html__(
            'Open the WordPress Customizer to preview and save settings. Installing the theme does not create articles, pages or media.',
            'pagenest',
        ) .
        '</p>';
    if (isset($_GET['imported']) && $_GET['imported'] === '1') {
        echo '<div class="notice notice-success"><p>' .
            esc_html__('Appearance settings imported.', 'pagenest') .
            '</p></div>';
    }
    echo '<h2>' .
        esc_html__('Appearance and content settings', 'pagenest') .
        '</h2><table class="widefat striped"><tbody>';
    $groups = [
        [
            'pagenest_appearance',
            __('Site appearance', 'pagenest'),
            __(
                'Palettes, colors and reading dimensions shared with the editor preview.',
                'pagenest',
            ),
        ],
        [
            'pagenest_home_composition',
            __('Home modules', 'pagenest'),
            __('Arrange and enable modules using existing public articles and pages.', 'pagenest'),
        ],
        [
            'pagenest_home_copy',
            __('Home introduction', 'pagenest'),
            __('Edit the home page introduction; leave empty to hide.', 'pagenest'),
        ],
        [
            'pagenest_home_image',
            __('Home background', 'pagenest'),
            __('Choose your own media; clear it to use a gradient.', 'pagenest'),
        ],
        [
            'pagenest_home_topics',
            __('Home topics', 'pagenest'),
            __('Configure home topic cards, descriptions and status labels.', 'pagenest'),
        ],
        [
            'pagenest_home_authors',
            __('Home authors', 'pagenest'),
            __('Choose the authors to display and edit their introduction.', 'pagenest'),
        ],
        [
            'pagenest_browsing',
            __('Lists and articles', 'pagenest'),
            __('List density, covers, updated dates and estimated reading time.', 'pagenest'),
        ],
        [
            'pagenest_login',
            __('Login and registration', 'pagenest'),
            __(
                'Background and notice. Authentication is handled by WordPress and optional plugins.',
                'pagenest',
            ),
        ],
        [
            'pagenest_footer',
            __('Footer information', 'pagenest'),
            __('Tagline, copyright owner and optional registration number.', 'pagenest'),
        ],
        [
            'pagenest_navigation',
            __('Archive and about links', 'pagenest'),
            __('Set archive and about links.', 'pagenest'),
        ],
        [
            'pagenest_community',
            __('Community', 'pagenest'),
            __('Set the community description and links.', 'pagenest'),
        ],
    ];
    foreach ($groups as [$section, $title, $description]) {
        $url = add_query_arg('autofocus[section]', $section, admin_url('customize.php'));
        echo '<tr><th scope="row"><a href="' .
            esc_url($url) .
            '">' .
            esc_html($title) .
            '</a></th><td>' .
            esc_html($description) .
            '</td></tr>';
    }
    echo '</tbody></table><p>' .
        esc_html__('Current palette:', 'pagenest') .
        ' <code>' .
        esc_html(
            pagenest_sanitize_appearance_preset(
                get_theme_mod('pagenest_appearance_preset', 'default'),
            ),
        ) .
        '</code></p>';
    echo '<h2>' .
        esc_html__('Transfer appearance settings', 'pagenest') .
        '</h2><p>' .
        esc_html__(
            'Export only saved palettes, colors and dimensions. Articles, media IDs, links, accounts and plugin settings are excluded. Import replaces only the listed values; omitted settings and empty values retain their meaning.',
            'pagenest',
        ) .
        '</p>';
    echo '<form method="post" action="' .
        esc_url(admin_url('admin-post.php')) .
        '"><input type="hidden" name="action" value="pagenest_appearance_export">';
    wp_nonce_field('pagenest_appearance_transfer');
    submit_button(__('Download appearance JSON', 'pagenest'), 'secondary');
    echo '</form><form method="post" action="' .
        esc_url(admin_url('admin-post.php')) .
        '"><input type="hidden" name="action" value="pagenest_appearance_import">';
    wp_nonce_field('pagenest_appearance_transfer');
    echo '<p><label for="pagenest-json">' .
        esc_html__('Paste appearance JSON', 'pagenest') .
        '</label></p><textarea id="pagenest-json" name="pagenest_json" rows="8" class="large-text code" required maxlength="16384"></textarea>';
    submit_button(__('Import and save appearance', 'pagenest'));
    echo '</form><h2>' .
        esc_html__('Editable block examples', 'pagenest') .
        '</h2><p>' .
        esc_html__(
            'In the block editor, open Patterns → PageNest and choose a topic introduction, resource cards or article outline. Replace the placeholders; content is created only when you insert and save it.',
            'pagenest',
        ) .
        '</p>';
    echo '<h2>' .
        esc_html__('Local read-only diagnostics', 'pagenest') .
        '</h2><p>' .
        esc_html__(
            'Check versions, asset files and local interfaces only, without network requests or repairs. An available interface does not mean its module is enabled.',
            'pagenest',
        ) .
        '</p><table class="widefat striped"><tbody>';
    $theme = wp_get_theme(get_template());
    $rows = [
        'PageNest' => $theme->get('Version'),
        'WordPress' => get_bloginfo('version'),
        'PHP' => PHP_VERSION,
    ];
    foreach (
        [
            'Companion' => 'pagenest_companion_config',
            __('Companion comments interface', 'pagenest') => 'pagenest_companion_feature',
            __('Companion content interface', 'pagenest') => 'pagenest_companion_content_config',
            __('Companion tools interface', 'pagenest') => 'PageNest\\Tools\\enabled',
            __('Companion search interface', 'pagenest') => 'pagenest_discovery_options',
        ]
        as $label => $function
    ) {
        $rows[$label] = function_exists($function)
            ? __('Interface available', 'pagenest')
            : __('Interface unavailable', 'pagenest');
    }
    foreach (pagenest_product_diagnostics() as $key => $healthy) {
        $rows[sprintf(__('Asset: %s', 'pagenest'), $key)] = $healthy
            ? __('Asset hash verified', 'pagenest')
            : __('Asset missing or hash mismatch', 'pagenest');
    }
    foreach ($rows as $label => $value) {
        echo '<tr><th scope="row">' .
            esc_html($label) .
            '</th><td>' .
            esc_html($value) .
            '</td></tr>';
    }
    echo '</tbody></table>';
    if (function_exists('pagenest_companion_feature') && current_user_can('manage_options')) {
        echo '<p><a href="' .
            esc_url(admin_url('options-general.php?page=pagenest-companion')) .
            '">' .
            esc_html__('Open Companion settings', 'pagenest') .
            '</a></p>';
    }
    echo '</div>';
}
add_action('admin_menu', static function () {
    add_theme_page(
        'PageNest',
        'PageNest',
        'edit_theme_options',
        'pagenest',
        'pagenest_product_page',
    );
});

function pagenest_product_patterns()
{
    if (
        !function_exists('register_block_pattern') ||
        !function_exists('register_block_pattern_category')
    ) {
        return;
    }
    register_block_pattern_category('pagenest', ['label' => 'PageNest']);
    $heading = static fn($text) => '<!-- wp:heading --><h2>' .
        esc_html($text) .
        '</h2><!-- /wp:heading -->';
    $paragraph = static fn($text) => '<!-- wp:paragraph --><p>' .
        esc_html($text) .
        '</p><!-- /wp:paragraph -->';
    $patterns = [
        'topic-introduction' => [
            __('Topic introduction', 'pagenest'),
            $heading(__('Enter a topic title', 'pagenest')) .
            $paragraph(
                __(
                    'Introduce the topic, intended audience and suggested reading order. Replace this text with your own content.',
                    'pagenest',
                ),
            ),
        ],
        'resource-cards' => [
            __('Resource cards', 'pagenest'),
            '<!-- wp:columns --><div class="wp-block-columns">' .
            '<!-- wp:column --><div class="wp-block-column">' .
            $heading(__('Resource one', 'pagenest')) .
            $paragraph(
                __('Describe this resource and add your own link using the editor.', 'pagenest'),
            ) .
            '</div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column">' .
            $heading(__('Resource two', 'pagenest')) .
            $paragraph(
                __('Describe this resource and add your own link using the editor.', 'pagenest'),
            ) .
            '</div><!-- /wp:column --></div><!-- /wp:columns -->',
        ],
        'article-outline' => [
            __('Article outline', 'pagenest'),
            $heading(__('Problem and background', 'pagenest')) .
            $paragraph(
                __(
                    'Describe the problem, assumptions and background readers will need.',
                    'pagenest',
                ),
            ) .
            $heading(__('Method and examples', 'pagenest')) .
            $paragraph(__('Describe your method and add your own examples.', 'pagenest')) .
            $heading(__('Summary and next steps', 'pagenest')) .
            $paragraph(
                __(
                    'Summarize the conclusions, limitations and suggested further reading.',
                    'pagenest',
                ),
            ),
        ],
    ];
    foreach ($patterns as $name => [$title, $content]) {
        register_block_pattern('pagenest/' . $name, [
            'title' => $title,
            'categories' => ['pagenest'],
            'content' => $content,
        ]);
    }
}
add_action('init', 'pagenest_product_patterns');
