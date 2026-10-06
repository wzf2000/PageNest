<?php
/** Synthetic WordPress fixture: no database, accounts, cookies, or network writes. */
define('ABSPATH', __DIR__);
const THEME = __DIR__ . '/..';
$mods = [];
$filters = [];
$actions = [];
$allowed = [];
$meta = [];
$current = 1001;
$posts_page_id = 2000;
$slug = '';
$loop = 0;
$checks = [];
class WP_Post
{
    public $ID,
        $post_type,
        $post_status,
        $post_title,
        $post_password = '',
        $post_author = 1;
    public $post_excerpt = '',
        $post_content = '';
    public function __construct($id, $status = 'publish', $type = 'post')
    {
        $this->ID = $id;
        $this->post_status = $status;
        $this->post_type = $type;
        $this->post_title = 'Synthetic article ' . $id;
        $this->post_content =
            '<a id="legacy-heading"></a>' .
            str_repeat(
                '<h2>Reading <span class="mbb-math" data-mbb-tex="x^2">x squared</span><button aria-hidden="true">ignore</button> heading</h2><h3>Child heading</h3><h4>Deep heading</h4><p>Public synthetic content for theme layout verification.</p>',
                6,
            );
    }
}
$posts = [];
foreach (range(1001, 1010) as $id) {
    $posts[$id] = new WP_Post($id);
}
$posts[1003]->post_status = 'private';
$posts[1004]->post_password = 'synthetic';
$posts[1005]->post_status = 'draft';
$posts[1006]->post_type = 'page';
class WP_Query
{
    public $posts = [];
    public function __construct($args)
    {
        $this->posts = [];
    }
}
class WP_Customize_Image_Control
{
    public function __construct(...$args) {}
}
class WP_Customize_Media_Control
{
    public function __construct(...$args) {}
}
class CustomizeFixture
{
    public $settings = [],
        $controls = [];
    public function add_section(...$args) {}
    public function add_panel(...$args) {}
    public function add_setting($id, $args)
    {
        $this->settings[$id] = $args;
    }
    public function add_control($id, $args = null)
    {
        if (is_string($id)) {
            $this->controls[$id] = $args;
        }
    }
}
function add_filter($tag, $callback, $priority = 10, $argc = 1)
{
    global $filters;
    $filters[$tag][$priority][] = [$callback, $argc];
}
function add_action($tag, $callback, $priority = 10, $argc = 1)
{
    global $actions;
    $actions[$tag][$priority][] = [$callback, $argc];
}
function apply_filters($tag, $value, ...$args)
{
    global $filters;
    $callbacks = $filters[$tag] ?? [];
    ksort($callbacks);
    foreach ($callbacks as $group) {
        foreach ($group as [$callback, $argc]) {
            $value = $callback(...array_slice([$value, ...$args], 0, $argc));
        }
    }
    return $value;
}
function do_action($tag, ...$args)
{
    global $actions;
    $callbacks = $actions[$tag] ?? [];
    ksort($callbacks);
    foreach ($callbacks as $group) {
        foreach ($group as [$callback, $argc]) {
            $callback(...array_slice($args, 0, $argc));
        }
    }
}
function absint($value)
{
    return abs((int) $value);
}
function esc_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function esc_attr($value)
{
    return esc_html($value);
}
function esc_url($value)
{
    return preg_match('/^(javascript|data|vbscript):/i', trim($value)) ? '' : esc_attr($value);
}
function get_theme_mod($key, $default = false)
{
    global $mods;
    return array_key_exists($key, $mods) ? $mods[$key] : $default;
}
function get_option($key)
{
    global $legacy_mods, $posts_page_id;
    return match ($key) {
        'page_for_posts' => $posts_page_id,
        'stylesheet' => 'pagenest',
        'theme_mods_wzf-journal' => $legacy_mods ?? [],
        default => null,
    };
}
function get_theme_mods()
{
    global $mods;
    return $mods;
}
function set_theme_mod($key, $value)
{
    global $mods;
    $mods[$key] = $value;
}
function pagenest_login_notice_default()
{
    return '';
}
function home_url($path = '/')
{
    return 'http://127.0.0.1:18779' . $path;
}
function get_permalink($id = null)
{
    return home_url('/post-' . (is_object($id) ? $id->ID : $id));
}
function get_bloginfo($key)
{
    return $key === 'charset' ? 'UTF-8' : 'Synthetic Site';
}
function bloginfo($key)
{
    echo esc_html(get_bloginfo($key));
}
function wp_date($format)
{
    return '2026';
}
function get_post($id)
{
    global $posts;
    return $posts[(int) $id] ?? null;
}
function wp_get_post_categories($id)
{
    return $id === 1002 ? [] : [9];
}
function get_posts($args)
{
    global $posts, $meta;
    if (($args['numberposts'] ?? 0) === -1) {
        return array_values(
            array_filter(
                $posts,
                fn($p) => $p->post_status === 'publish' && $p->post_type === 'post',
            ),
        );
    }
    return [$posts[1007], $posts[1008], $posts[1009]];
}
function current_user_can($capability, $id = null)
{
    global $allowed;
    return in_array($id, $allowed, true);
}
function post_password_required($post = null)
{
    return !empty(($post ?: get_post(get_the_ID()))->post_password);
}
function get_post_meta($id, $key, $single = false)
{
    global $meta;
    return $meta[$id][$key] ?? '';
}
function get_queried_object_id()
{
    return get_the_ID();
}
function get_the_ID()
{
    global $current;
    return $current;
}
function the_ID()
{
    echo get_the_ID();
}
function get_post_field($field, $id)
{
    return get_post($id)->$field;
}
function get_the_date($format, $post = null)
{
    return match ($format) {
        'Y年n月' => '2026年10月',
        'm/d' => '10/05',
        'Y-m-d' => '2026-10-05',
        default => '2026-10-05',
    };
}
function get_the_author_meta($field, $id)
{
    return 'Synthetic Author';
}
function get_author_posts_url($id)
{
    return home_url('/author/' . $id);
}
function get_avatar(...$args)
{
    return '<span class="avatar">S</span>';
}
function get_post_status()
{
    return get_post(get_the_ID())->post_status;
}
function is_singular($type)
{
    return get_post(get_the_ID())->post_type === $type;
}
function get_page_template_slug()
{
    global $slug;
    return $slug;
}
function have_posts()
{
    global $loop;
    return $loop === 0;
}
function the_post()
{
    global $loop;
    $loop++;
}
function the_content()
{
    echo get_post(get_the_ID())->post_content;
}
function has_post_thumbnail(...$args)
{
    return false;
}
function post_class($class)
{
    echo 'class="' . esc_attr($class) . '"';
}
function wp_link_pages(...$args) {}
function get_comments_number()
{
    return 0;
}
function comments_open()
{
    return false;
}
function edit_post_link(...$args) {}
function get_adjacent_post(...$args)
{
    return null;
}
function language_attributes()
{
    echo 'lang="zh-CN"';
}
function body_class()
{
    echo 'class="pagenest-site"';
}
function wp_body_open() {}
function get_nav_menu_locations()
{
    return [];
}
function wp_get_nav_menu_items(...$args)
{
    return [];
}
function is_user_logged_in()
{
    return false;
}
function wp_login_url()
{
    return home_url('/login');
}
function wp_head()
{
    $manifest = require THEME . '/assets/manifest.php';
    echo '<link rel="stylesheet" href="/assets/' . $manifest['css'] . '">';
}
function wp_footer()
{
    $manifest = require THEME . '/assets/manifest.php';
    echo '<script src="/assets/' .
        $manifest['js'] .
        '"></script><script src="/assets/' .
        $manifest['reading'] .
        '"></script>';
}
function get_header()
{
    require THEME . '/header.php';
}
function get_footer()
{
    require THEME . '/footer.php';
}
function wp_tag_cloud(...$args) {}
function render($template)
{
    global $loop;
    $loop = 0;
    ob_start();
    require THEME . '/' . $template;
    return ob_get_clean();
}
function check($name, $value)
{
    global $checks;
    $checks[] = ['name' => $name, 'passed' => (bool) $value];
    if (!$value) {
        throw new RuntimeException($name);
    }
}
require THEME . '/inc.php';
require THEME . '/reading.php';
require THEME . '/customizer.php';
require THEME . '/legacy-migration.php';
$legacy_mods = [
    'wzfj_home_intro' => 'old',
    'wzfj_footer_tagline' => 'old tagline',
    'wzfj_home_eyebrow' => 'old eyebrow',
    'wzfj_authors_intro' => 'old authors',
    'wzfj_footer_owner' => 'legacy owner',
    'nav_menu_locations' => ['wzfl_primary' => 17, 'legacy_footer' => 18],
];
$mods = [
    'pagenest_home_intro' => '',
    'pagenest_footer_tagline' => null,
    'pagenest_home_eyebrow' => false,
    'pagenest_authors_intro' => 'current',
    'nav_menu_locations' => ['pagenest_primary' => 99, 'current_footer' => 100],
];
$old_theme = new class {
    public function get_stylesheet()
    {
        return 'wzf-journal';
    }
};
pagenest_migrate_legacy_theme('', $old_theme);
check(
    'legacy migration preserves empty null false current',
    $mods['pagenest_home_intro'] === '' &&
        $mods['pagenest_footer_tagline'] === null &&
        $mods['pagenest_home_eyebrow'] === false &&
        $mods['pagenest_authors_intro'] === 'current',
);
check('legacy migration fills absent setting', $mods['pagenest_footer_owner'] === 'legacy owner');
check(
    'legacy navigation fills only absent location',
    $mods['nav_menu_locations'] === [
        'pagenest_primary' => 99,
        'current_footer' => 100,
        'legacy_footer' => 18,
    ],
);
$before = $mods;
pagenest_migrate_legacy_theme('', $old_theme);
check('switch back from legacy idempotent', $mods === $before);
$mods = ['nav_menu_locations' => null];
pagenest_migrate_legacy_theme('', $old_theme);
check('explicit null navigation retained', $mods['nav_menu_locations'] === null);
$mods = [];
pagenest_migrate_legacy_theme('', $old_theme);
check(
    'initial legacy navigation mapping',
    $mods['nav_menu_locations'] === [
        'legacy_footer' => 18,
        'pagenest_primary' => 17,
    ],
);
$mods = [];
$legacy_mods = [];
$posts_page_id = 0;
check(
    'posts URL without configured page avoids current permalink',
    pagenest_posts_url() === home_url('/'),
);
check(
    'default archive URL without configured page is home',
    pagenest_setting_defaults()['pagenest_archive_link_url'] === home_url('/'),
);
$posts_page_id = 2000;
$customizer = new CustomizeFixture();
do_action('customize_register', $customizer);
$defaults = pagenest_setting_defaults();
check(
    'all twelve settings registered',
    count($defaults) === 12 && count(array_intersect_key($customizer->settings, $defaults)) === 12,
);
foreach ($defaults as $key => $value) {
    check(
        $key . ' editor capability',
        $customizer->settings[$key]['capability'] === 'edit_theme_options',
    );
}
check(
    'textarea sanitizer',
    $customizer->settings['pagenest_home_intro']['sanitize_callback'] === 'sanitize_textarea_field',
);
check(
    'url sanitizer',
    $customizer->settings['pagenest_community_link_url']['sanitize_callback'] === 'esc_url_raw',
);
check(
    'default related categories',
    array_column(pagenest_related(1001), 'ID') === [1007, 1008, 1009],
);
check('no categories no default related', pagenest_related(1002) === []);
$filters['pagenest_related_posts'] = [
    10 => [[fn() => [1001, 1003, 1004, 1005, 1006, 9999, 1007, 1007, 1008, 1009, 1010], 2]],
];
check(
    'extension results permission password type duplicate limit',
    array_column(pagenest_related(1001), 'ID') === [1007, 1008, 1009],
);
$allowed = [1003];
check(
    'authorized private accepted after extension',
    array_column(pagenest_related(1001), 'ID') === [1003, 1007, 1008],
);
$filters['pagenest_related_posts'] = [10 => [[fn() => null, 2]]];
check('invalid extension output safe', pagenest_related(1001) === []);
$filters = [];
$allowed = [];
$default_home = render('front-page.php');
$mods = [
    'pagenest_home_eyebrow' => '<img src=x onerror=alert(1)>',
    'pagenest_home_intro' => "<script>window.injected=1</script>\nLine two",
    'pagenest_authors_intro' => "Author <b>intro</b>\nSecond line",
    'pagenest_footer_tagline' => '<script>Tagline</script>',
    'pagenest_community_description' => '<script>Community</script>',
    'pagenest_community_link_label' => '<b>Community link →</b>',
    'pagenest_community_link_url' => home_url('/updates'),
    'pagenest_archive_link_label' => 'Footer archive',
    'pagenest_community_archive_label' => 'Sidebar archive →',
    'pagenest_archive_link_url' => home_url('/archive-page'),
    'pagenest_about_link_label' => 'About <b>us</b>',
    'pagenest_about_link_url' => home_url('/about-page'),
];
$home = render('front-page.php');
check(
    'home intro escaped and line breaks preserved',
    str_contains($home, '&lt;script&gt;window.injected=1&lt;/script&gt;<br />') &&
        !str_contains($home, '<script>window.injected'),
);
check('home eyebrow escaped', str_contains($home, '&lt;img src=x onerror=alert(1)&gt;'));
check(
    'footer brand tagline escaped and two columns',
    str_contains($home, '&lt;script&gt;Tagline&lt;/script&gt;') &&
        substr_count($home, 'Synthetic Site') >= 4,
);
$article = render('singular.php');
check(
    'sidebar description and labels escaped',
    str_contains($article, '&lt;script&gt;Community&lt;/script&gt;') &&
        str_contains($article, '&lt;b&gt;Community link →&lt;/b&gt;'),
);
check(
    'separate archive label',
    str_contains($article, 'Sidebar archive →') &&
        str_contains($article, 'Footer archive') &&
        !str_contains($article, '→ →'),
);
check('public no integration action absent', !str_contains($article, 'class="favorite"'));
$rendered = [
    'home-default.html' => $default_home,
    'home-configured.html' => $home,
    'article-public.html' => $article,
];
$mods = array_fill_keys(array_keys($defaults), '');
$empty = render('front-page.php');
check(
    'empty configured intro and footer tagline hidden',
    !str_contains($empty, 'pagenest-eyebrow"></p>') &&
        !str_contains($empty, 'Tagline') &&
        !str_contains($empty, 'href=""'),
);
ob_start();
pagenest_side(true);
$side = ob_get_clean();
check(
    'empty sidebar links hidden',
    !str_contains($side, '/updates') && !str_contains($side, 'href=""'),
);
$rendered['home-empty.html'] = $empty;
$mods['pagenest_community_link_label'] = 'unsafe';
$mods['pagenest_community_link_url'] = 'javascript:alert(1)';
ob_start();
pagenest_side(true);
$side = ob_get_clean();
check(
    'unsafe link hidden',
    !str_contains($side, 'javascript:') && !str_contains($side, '>unsafe<'),
);
$posts[1001]->post_type = 'page';
foreach (['page-archives.php', 'pages/page-archives.php'] as $slug) {
    $html = render('singular.php');
    check($slug . ' compatible archive', str_contains($html, 'pagenest-archive-month'));
    $posts[1001]->post_password = 'synthetic';
    $html = render('singular.php');
    check($slug . ' archive password preserved', !str_contains($html, 'pagenest-archive-month'));
    $posts[1001]->post_password = '';
}
$slug = 'page-archives.php';
check(
    'registered root template renders archive',
    str_contains(render('page-archives.php'), 'pagenest-archive-month'),
);
$slug = '';
$posts[1001]->post_type = 'post';
$mods = [];
add_action('pagenest_article_actions', function () {
    echo '<button class="fixture-action" data-pagenest-header-action="fixture">Extension</button>';
});
$integrated = render('singular.php');
check('optional action rendered', str_contains($integrated, 'fixture-action'));
$posts[1001]->post_password = 'synthetic';
check(
    'password footer action not invoked',
    !str_contains(render('singular.php'), 'fixture-action'),
);
$posts[1001]->post_password = '';
$rendered['article-integrated.html'] = $integrated;
$out = THEME . '/.runtime/frontend';
if (!is_dir($out)) {
    mkdir($out, 0777, true);
}
if (!is_dir($out . '/assets')) {
    mkdir($out . '/assets');
}
$manifest = require THEME . '/assets/manifest.php';
foreach ($manifest as $name) {
    copy(THEME . '/assets/' . $name, $out . '/assets/' . $name);
}
foreach ($rendered as $name => $content) {
    file_put_contents($out . '/' . $name, $content);
}
echo count($checks) . " theme PHP checks passed\n";
