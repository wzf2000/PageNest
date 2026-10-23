<?php
/** Synthetic WordPress fixture: no database, accounts, cookies, or network writes. */
define('ABSPATH', __DIR__);
require __DIR__ . '/i18n-stubs.php';
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
$fixture_search = false;
$fixture_search_results = false;
$fixture_browsing = false;
$fixture_queries = [];
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
        $titles = [
            '让知识沉淀下来：建立可持续的阅读习惯',
            '从问题出发，设计清晰的技术笔记',
            '认识数据结构中的时间与空间',
            '在复杂系统里找到稳定的边界',
            '一次小规模实验的记录',
            '专题与系列导览',
            '理解概率：从直觉走向推导',
            '把抽象概念写成可运行的代码',
            '做一个长期维护的个人知识库',
            '用简单的工具组织日常工作',
        ];
        $this->post_title = $titles[$id - 1001] ?? '阅读笔记';
        $this->post_content =
            '<a id="legacy-heading"></a><p>阅读不是把所有信息存下来，而是为下一次思考留下可以重新进入的线索。先提出一个问题，再用例子检验自己的理解，最后写下仍然没有解决的部分。</p><table style="min-width:720px"><tr><th>阅读阶段</th><th>值得留下的问题</th></tr><tr><td>建立直觉</td><td>它解决了什么问题？</td></tr></table><span class="pagenest-exercise-hint">先独立思考，再查看提示。</span>';
        foreach (
            [
                '从一个具体问题开始',
                '让例子帮助理解',
                '区分事实与假设',
                '建立知识之间的联系',
                '回到实践中检验',
                '给后续阅读留下线索',
            ]
            as $heading
        ) {
            $this->post_content .=
                '<h2>' .
                $heading .
                ' <span class="mbb-math" data-mbb-tex="x^2">x squared</span><button aria-hidden="true">ignore</button></h2><p>一个好问题能够连接分散的材料。把术语放回它原本的使用场景，比孤立地记住定义更容易形成稳定的理解。</p><h3>观察与记录</h3><p>记录一次尝试的条件和结果，也记录没有奏效的方向。简洁的笔记应当让未来的自己快速找回推理过程。</p><h4>下一步可以尝试什么</h4><p>选择一个足够小的例子，把理解转化成可以检查的步骤，然后回看最初的问题。</p>';
        }
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
    public $query_vars = [];
    public function is_main_query()
    {
        return true;
    }
    public function is_search()
    {
        return true;
    }
    public function set($key, $value)
    {
        $this->query_vars[$key] = $value;
    }
    public function __construct($args)
    {
        global $fixture_browsing, $fixture_queries, $posts;
        $fixture_queries[] = $args;
        $this->posts = $fixture_browsing ? array_values($posts) : [];
    }
}
class WP_Customize_Color_Control
{
    public function __construct(...$args) {}
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
    public $sections = [],
        $panels = [];
    public function add_section($id, $args)
    {
        $this->sections[$id] = (object) $args;
    }
    public function get_section($id)
    {
        return $this->sections[$id] ?? null;
    }
    public function add_panel($id, $args)
    {
        $this->panels[$id] = $args;
    }
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
    return $key === 'charset' ? 'UTF-8' : '栖页 · 阅读与思考';
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
    if (($args['numberposts'] ?? 0) === 100) {
        return array_values($posts);
    }
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
    return '编辑团队';
}
function get_author_posts_url($id)
{
    return home_url('/author/' . $id);
}
function get_avatar(...$args)
{
    return '<span class="avatar">页</span>';
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
    global $loop, $fixture_search, $fixture_search_results;
    return (!$fixture_search || $fixture_search_results) && $loop === 0;
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
    return !empty($GLOBALS['fixture_thumbnails']);
}
function get_the_post_thumbnail(...$args)
{
    return '<img src="/assets/fixture-cover.svg" alt="">';
}
function the_post_thumbnail(...$args)
{
    echo get_the_post_thumbnail();
}
function strip_shortcodes($text)
{
    return preg_replace('/\[.*?\]/s', '', $text);
}
function number_format_i18n($value)
{
    return number_format($value);
}
function get_the_category($id)
{
    return [];
}
function get_the_modified_date($format, $post)
{
    return '2026-10-13';
}
function get_post_modified_time(...$args)
{
    return 1791849600;
}
function get_post_time(...$args)
{
    return 1791763200;
}
function is_category()
{
    return false;
}
function is_tag()
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
    global $fixture_locale;
    echo $fixture_locale === 'zh_CN' ? 'lang="zh-CN"' : 'lang="en-US"';
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
function get_search_form()
{
    require THEME . '/searchform.php';
}
function wp_unique_id($prefix = '')
{
    static $id = 0;
    return $prefix . ++$id;
}
function get_feed_link()
{
    return 'https://example.invalid/feed/';
}
function get_search_query()
{
    return 'Synthetic';
}
function sanitize_text_field($value)
{
    return strip_tags($value);
}
function sanitize_key($value)
{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower($value));
}
function wp_unslash($value)
{
    return stripslashes($value);
}
function selected($value, $expected, $echo = true)
{
    $selected = $value === $expected ? 'selected="selected"' : '';
    if ($echo) {
        echo $selected;
    }
    return $selected;
}
function is_admin()
{
    return false;
}
function is_search()
{
    global $fixture_search;
    return $fixture_search;
}
function is_404()
{
    return false;
}
function is_home()
{
    return !is_search();
}
function paginate_links($args = [])
{
    return '<a href="/?s=Synthetic&amp;pn_kind=page&amp;paged=2">2</a>';
}
function wp_strip_all_tags($value)
{
    return strip_tags($value);
}
function get_the_archive_title()
{
    return 'Synthetic archive';
}
function get_categories($args = [])
{
    return [];
}
function wp_json_encode($value)
{
    return json_encode($value);
}
function wp_head()
{
    pagenest_initialize_color_scheme();
    $manifest = require THEME . '/assets/manifest.php';
    foreach (
        [
            'css',
            'content',
            'appearance',
            'discovery_css',
            'browsing',
            'reader_preferences_css',
            'presentation_css',
            'editorial',
        ]
        as $key
    ) {
        echo '<link rel="stylesheet" href="/assets/' . $manifest[$key] . '">';
    }
    echo '<style>' . pagenest_appearance_css() . '</style>';
    if (get_theme_mod('pagenest_hero_attachment', 0)) {
        echo '<style>:root{--pagenest-hero-image:url("/assets/fixture-cover.svg")}</style>';
    }
}
function wp_footer()
{
    $names = [
        'pagenest-content' => 'pagenestContentL10n',
        'pagenest-reading' => 'pagenestReadingL10n',
        'pagenest-discovery' => 'pagenestDiscoveryL10n',
        'pagenest-reader-preferences' => 'pagenestReaderL10n',
    ];
    foreach (pagenest_script_translations() as $handle => $strings) {
        echo '<script>window.' .
            $names[$handle] .
            '=' .
            json_encode($strings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) .
            ';</script>';
    }
    $manifest = require THEME . '/assets/manifest.php';
    echo '<script>window.PageNestPresentation=' .
        json_encode([
            'defaultScheme' => 'light',
            'saved' => 'Saved',
            'unsaved' => 'Applied to this page',
        ]) .
        ';</script>';
    foreach (
        ['content_js', 'js', 'reading', 'discovery_js', 'reader_preferences_js', 'presentation_js']
        as $key
    ) {
        echo '<script src="/assets/' . $manifest[$key] . '"></script>';
    }
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
require THEME . '/i18n.php';
require THEME . '/inc.php';
require THEME . '/reading.php';
require THEME . '/appearance.php';
require THEME . '/presentation.php';
require THEME . '/discovery.php';
require THEME . '/browsing.php';
require THEME . '/home-sections.php';
require THEME . '/reader-preferences.php';
require THEME . '/customizer.php';
require THEME . '/design.php';
$mods = [];
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
check(
    'native design panel groups own sections',
    count($customizer->panels) >= 1 &&
        $customizer->sections['pagenest_appearance']->panel === 'pagenest_design',
);
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
        substr_count($home, '栖页 · 阅读与思考') >= 3,
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
$rendered['page-archives.html'] = render('page-archives.php');
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
$mods = [
    'pagenest_appearance_preset' => 'warm',
    'pagenest_appearance_reading-size' => 22,
    'pagenest_appearance_font-size' => 18,
    'pagenest_appearance_container' => 1320,
    'pagenest_appearance_spacing' => 1.1,
    'pagenest_appearance_radius' => 18,
];
$rendered['article-appearance.html'] = render('singular.php');
check(
    'appearance rendered from shared server values',
    str_contains($rendered['article-appearance.html'], '--pagenest-reading-size:22px') &&
        str_contains($rendered['article-appearance.html'], '--pagenest-container:1320px'),
);
$appearance_css = pagenest_appearance_css();
$mods = ['pagenest_topics' => [['title' => 'Synthetic topic', 'url' => home_url('/topic')]]];
add_filter('pagenest_search_endpoint', fn() => home_url('/wp-json/pagenest/v1/discovery'));
$fixture_search = true;
$rendered['search-discovery.html'] = render('index.php');
add_filter('pagenest_search_series', fn() => ['阅读方法', '知识整理']);
$rendered['search-series.html'] = render('index.php');
$fixture_search_results = true;
$searchPost = get_post(get_the_ID());
$oldExcerpt = $searchPost->post_excerpt;
$oldContent = $searchPost->post_content;
$searchPost->post_excerpt = 'Synthetic public summary: tensor shapes [B,T,D] and [a,b].';
$rendered['search-results.html'] = render('index.php');
$_GET['pn_sort'] = 'modified';
$rendered['search-results-filtered.html'] = render('index.php');
unset($_GET['pn_sort']);
check(
    'search fixture renders real result cards and explicit excerpt',
    str_contains(
        strip_tags($rendered['search-results.html']),
        'Synthetic public summary: tensor shapes [B,T,D] and [a,b].',
    ),
);
$searchPost->post_excerpt = '[unknown:private secret="PRIVATE"]';
$searchPost->post_content =
    '<!-- hidden metadata --><p>Public tensor [B,T,D]</p><script>SECRET</script>';
check(
    'search skips unsafe excerpt and metadata',
    pagenest_search_result_details($searchPost) === 'Public tensor [B,T,D]',
);
$searchPost->post_excerpt = '';
$searchPost->post_content = '[unknown.private secret="PRIVATE"]';
check(
    'search rejects unknown punctuation shortcode tags',
    pagenest_search_result_details($searchPost) === '',
);
$searchPost->post_excerpt = $oldExcerpt;
$searchPost->post_content = $oldContent;
$fixture_search_results = false;

unset($filters['pagenest_search_series']);
ob_start();
pagenest_side();
$search_sidebar = ob_get_clean();
check(
    'search sidebar omits only its duplicate search form',
    !str_contains($search_sidebar, 'role="search"') &&
        str_contains($search_sidebar, __('Browse categories', 'pagenest')) &&
        str_contains($search_sidebar, __('Popular articles', 'pagenest')),
);
check(
    'search page retains main and header search forms',
    substr_count($rendered['search-discovery.html'], 'role="search"') === 2,
);
$fixture_search = false;
ob_start();
pagenest_side();
check(
    'ordinary listing sidebar retains its search form',
    str_contains(ob_get_clean(), 'role="search"'),
);
check(
    'search form unique IDs',
    preg_match_all('/id="(pagenest-search-\d+)"/', $rendered['search-discovery.html'], $ids) &&
        count($ids[1]) === count(array_unique($ids[1])),
);
check('search selected scope and invalid scope fallback', pagenest_search_kind() === 'all');
foreach (
    ['post' => 'post', 'page' => 'page', 'invalid' => ['post', 'page']]
    as $input => $expected
) {
    $_GET['pn_kind'] = $input;
    $query = new WP_Query([]);
    do_action('pre_get_posts', $query);
    check('native search scope ' . $input, $query->query_vars['post_type'] === $expected);
}
$_GET = [];
require __DIR__ . '/browsing-fixtures.php';
$fixture_locale = 'en_US';
$mods = [];
$current = 1001;
$rendered['article-english.html'] = render('singular.php');
$rendered['home-english.html'] = render('front-page.php');
$fixture_search = true;
$rendered['search-english.html'] = render('index.php');
$fixture_search = false;
check(
    'English native messages',
    str_contains($rendered['home-english.html'], 'Browse articles') &&
        !str_contains($rendered['home-english.html'], '探索专题'),
);
$mods = ['pagenest_home_intro' => '保留管理员自己的文案', 'pagenest_footer_tagline' => ''];
check(
    'Saved copy and intentional blank not translated',
    pagenest_setting('pagenest_home_intro') === '保留管理员自己的文案' &&
        pagenest_setting('pagenest_footer_tagline') === '',
);
check(
    'English plurals',
    pagenest_comment_count(1) === '1 comment' && pagenest_comment_count(2) === '2 comments',
);
$fixture_locale = 'zh_CN';
check(
    'Chinese catalog plurals',
    pagenest_comment_count(1) === '1 条评论' && pagenest_comment_count(2) === '2 条评论',
);
$mods = [
    'pagenest_home_intro' => '围绕真实的问题阅读、实践与记录。
让每一页成为下一次思考的起点。',
    'pagenest_home_eyebrow' => '合成演示 · 知识与思考',
    'pagenest_topics' => [
        [
            'title' => '技术与实践',
            'url' => home_url('/topic'),
            'description' => '从基本概念出发，把理解写进实践。',
            'status' => '持续更新',
        ],
        [
            'title' => '阅读与笔记',
            'url' => home_url('/notes'),
            'description' => '留下问题、线索与值得重读的片段。',
        ],
        [
            'title' => '工具与方法',
            'url' => home_url('/tools'),
            'description' => '用简单的方法组织长期工作。',
        ],
    ],
];
$mods['pagenest_authors'] = [
    ['name' => '编辑团队'],
    ['name' => '读者来稿'],
    ['name' => '方法记录'],
];
$fixture_browsing = true;
$fixture_thumbnails = true;
$rendered['editorial-home.html'] = render('front-page.php');
$mods['pagenest_hero_attachment'] = 321;
$rendered['editorial-home-image.html'] = render('front-page.php');
$mods['pagenest_hero_attachment'] = 0;
$mods['pagenest_home_featured_enabled'] = true;
$mods['pagenest_home_featured_0'] = 1001;
$mods['pagenest_home_featured_1'] = 1002;
$rendered['editorial-home-featured.html'] = render('front-page.php');
$posts[1001]->post_title = '让知识沉淀下来：建立可持续的阅读习惯';
$rendered['editorial-article.html'] = render('singular.php');
$posts[1001]->post_title = str_repeat('一个需要完整呈现的长标题：从问题出发理解设计，', 8);
$rendered['editorial-long-title.html'] = render('singular.php');
foreach ([14, 20] as $base_size) {
    $mods['pagenest_appearance_font-size'] = $base_size;
    $rendered['editorial-home-font-' . $base_size . '.html'] = render('front-page.php');
    $rendered['editorial-article-font-' . $base_size . '.html'] = render('singular.php');
    $rendered['editorial-list-font-' . $base_size . '.html'] = render('index.php');
}

$mods['pagenest_appearance_font-size'] = 16;
$posts[1001]->post_title = '建立可持续的阅读习惯';
$filters['pagenest_series_directory'] = [
    10 => [
        [
            fn() => [
                'url' => 'https://example.test/contents/',
                'title' => '系列目录：从概念到实践',
            ],
            2,
        ],
    ],
];
$rendered['article-series.html'] = render('singular.php');
check(
    'mapped series directory displayed',
    str_contains($rendered['article-series.html'], 'data-pagenest-series-directory'),
);
unset($filters['pagenest_series_directory']);

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
file_put_contents(
    $out . '/assets/fixture-cover.svg',
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360"><rect width="640" height="360" fill="#e4e7de"/><circle cx="490" cy="90" r="55" fill="#d2b78d"/><path d="M0 360L200 120 350 300 450 200 640 360" fill="#7f9586"/></svg>',
);
foreach ($rendered as $name => $content) {
    file_put_contents($out . '/' . $name, $content);
}
$preview =
    '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/' .
    $manifest['content'] .
    '"><div class="editormd"><textarea id="source">&lt;span class="fixture-hint"&gt;Unchanged source&lt;/span&gt;</textarea><div class="editormd-preview-container"><span class="fixture-hint">Initial hint</span></div></div><span class="fixture-hint" id="outside-preview">Outside preview</span><script>window.PageNestContentAliases={"fixture-hint":"pagenest-exercise-hint","fixture-invalid":"unsafe class"};</script><script src="/assets/' .
    $manifest['content_js'] .
    '"></script>';
file_put_contents($out . '/editor-preview.html', $preview);
file_put_contents(
    $out . '/editor-appearance.html',
    '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/' .
        $manifest['appearance'] .
        '"><link rel="stylesheet" href="/assets/' .
        $manifest['content'] .
        '"><style>' .
        $appearance_css .
        '</style><div class="editor-styles-wrapper"><p>Editor appearance consistency</p><h2>Heading</h2></div>',
);
echo count($checks) . " theme PHP checks passed\n";
