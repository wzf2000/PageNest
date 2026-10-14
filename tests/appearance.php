<?php
/** No WordPress database or network: exercise the CSS output trust boundary. */
define('ABSPATH', __DIR__);
require __DIR__ . '/i18n-stubs.php';
$appearance_mods = [];
$appearance_hooks = [];
$appearance_styles = [];
function get_theme_mod($key, $default = false)
{
    global $appearance_mods;
    return $appearance_mods[$key] ?? $default;
}
function add_action($tag, $callback, $priority = 10)
{
    global $appearance_hooks;
    $appearance_hooks[$tag][] = $callback;
}
function add_filter($tag, $callback)
{
    add_action($tag, $callback);
}
function get_theme_file_uri($path)
{
    return 'https://example.test/' . $path;
}
function wp_enqueue_style(...$args)
{
    global $appearance_styles;
    $appearance_styles['enqueue'] = $args;
}
function wp_add_inline_style($handle, $css)
{
    global $appearance_styles;
    $appearance_styles[$handle] = $css;
}
function wp_json_encode($value, $flags = 0)
{
    return json_encode($value, $flags);
}
class WP_Customize_Color_Control
{
    public function __construct(...$args) {}
}
class AppearanceCustomizer
{
    public $settings = [];
    public $controls = [];
    public function add_section(...$args) {}
    public function add_setting($key, $args)
    {
        $this->settings[$key] = $args;
    }
    public function add_control($key, $args = [])
    {
        if (is_string($key)) {
            $this->controls[$key] = $args;
        }
    }
}
function appearance_check($passed, $message)
{
    if (!$passed) {
        throw new RuntimeException($message);
    }
}
require __DIR__ . '/../appearance.php';
if (in_array('--tinymce-fixture', $argv, true)) {
    $css =
        "body{font-family:\"Quoted\",'single';content:'\\path';}\n" .
        "/* \u{2028}\u{2029} </script><script>window.injected=true</script> & */";
    $existing = 'existing{content:"already escaped";}';
    echo json_encode([
        'original' => $existing . ' ' . $css,
        'encoded' => pagenest_tinymce_css($existing) . ' ' . pagenest_tinymce_css($css),
        'theme' => $appearance_hooks['tiny_mce_before_init'][0]([])['content_style'],
        'themeOriginal' => ' ' . pagenest_appearance_css(),
    ]);
    exit();
}
$default = pagenest_appearance_variables();
appearance_check($default['--pagenest-reading-size'] === '17px', 'Existing reading size preserved');
appearance_check(
    $default['--pagenest-background'] === '#f7f6f2',
    'Editorial default background; saved overrides preserved',
);
$stylesheet = file_get_contents(__DIR__ . '/../assets/appearance.css');
foreach ($default as $key => $value) {
    appearance_check(
        str_contains($stylesheet, $key . ': ' . $value . ';'),
        'Stylesheet and server default agree: ' . $key,
    );
}
foreach (['', 'dark', [], new stdClass(), '</style><script>x</script>'] as $invalid) {
    $appearance_mods = ['pagenest_appearance_preset' => $invalid];
    appearance_check(pagenest_appearance_variables() === $default, 'Invalid preset falls back');
}
foreach (['warm', 'forest'] as $preset) {
    $appearance_mods = ['pagenest_appearance_preset' => $preset];
    $values = pagenest_appearance_variables();
    appearance_check(
        $values['--pagenest-background'] === pagenest_appearance_presets()[$preset]['background'],
        'Selected preset used',
    );
    $appearance_mods['pagenest_appearance_accent'] = '#AbC';
    $values = pagenest_appearance_variables();
    appearance_check(
        $values['--pagenest-accent'] === '#abc' && $values['--pagenest-content-link'] === '#abc',
        'Accent shared by content and layout',
    );
    $appearance_mods['pagenest_appearance_accent'] = '#fff;}</style><script>x</script>';
    appearance_check(
        pagenest_appearance_variables()['--pagenest-accent'] ===
            pagenest_appearance_presets()[$preset]['accent'],
        'Invalid color uses selected preset',
    );
}
foreach (pagenest_appearance_numbers() as $key => $definition) {
    foreach (['', '16px', false, [], new stdClass(), NAN, INF, '-INF', '</style>'] as $invalid) {
        appearance_check(
            pagenest_sanitize_appearance_number($invalid, $definition) === $definition['default'],
            'Invalid number uses default: ' . $key,
        );
    }
    appearance_check(
        pagenest_sanitize_appearance_number(-100, $definition) == $definition['min'],
        'Minimum enforced: ' . $key,
    );
    appearance_check(
        pagenest_sanitize_appearance_number(10000, $definition) == $definition['max'],
        'Maximum enforced: ' . $key,
    );
}
$appearance_mods = [
    'pagenest_appearance_reading-size' => 22,
    'pagenest_appearance_spacing' => '1.09',
];
$css = pagenest_appearance_css();
appearance_check(
    str_contains($css, '--pagenest-reading-size:22px') &&
        str_contains($css, '--pagenest-spacing:1.1'),
    'Numbers preserve units and snap to step',
);
pagenest_enqueue_appearance();
appearance_check(
    $appearance_styles['pagenest-appearance'] === $css,
    'Frontend emits shared sanitized values',
);
$settings = $appearance_hooks['block_editor_settings_all'][0]([
    'styles' => [['css' => 'existing{}']],
]);
appearance_check(
    $settings['styles'][0]['css'] === 'existing{}' && $settings['styles'][1]['css'] === $css,
    'Block editor canvas uses frontend values without removing styles',
);
$settings = $appearance_hooks['tiny_mce_before_init'][0](['content_style' => 'existing{}']);
appearance_check(
    json_decode('"' . $settings['content_style'] . '"') === 'existing{} ' . $css,
    'Classic editor JavaScript string restores frontend CSS without literal newlines',
);
$existing = pagenest_tinymce_css('existing{content:"preserved";}');
$settings = $appearance_hooks['tiny_mce_before_init'][0](['content_style' => $existing]);
appearance_check(
    str_starts_with($settings['content_style'], $existing . ' '),
    'Classic editor preserves previously escaped styles without double escaping',
);
$customizer = new AppearanceCustomizer();
pagenest_register_appearance_customizer($customizer);
appearance_check(count($customizer->settings) === 10, 'All appearance settings registered');
foreach ($customizer->settings as $key => $setting) {
    appearance_check(
        $setting['capability'] === 'edit_theme_options' && $setting['transport'] === 'refresh',
        'Settings permission and server preview: ' . $key,
    );
    appearance_check(
        is_callable($setting['sanitize_callback']),
        'Setting sanitizer exists: ' . $key,
    );
}
echo "Appearance defaults, presets, bounds, CSS injection and preview checks passed\n";
