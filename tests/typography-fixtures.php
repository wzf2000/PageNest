<?php
/** RTL samples use Arabic synthetic prose; shipped translations remain English/Chinese. */
function post_type_exists($type)
{
    return ($GLOBALS['fixture_fonts_enabled'] ?? false) &&
        in_array($type, ['wp_font_family', 'wp_font_face'], true);
}
function wp_get_font_dir()
{
    return [
        'basedir' => THEME . '/.runtime/frontend/assets/fonts',
        'baseurl' => home_url('/assets/fonts'),
        'error' => false,
    ];
}
function wp_parse_url($url)
{
    return parse_url($url);
}
$fixture_locale = 'ar';
$original = [$posts[1001]->post_title, $posts[1001]->post_content];
$posts[1001]->post_title = 'قراءة متأنية وأفكار مترابطة · PageNest 2026';
$posts[1001]->post_content =
    '<h2 id="rtl-start">ابدأ بالقراءة والاستكشاف</h2><p>هذه فقرة عربية تجريبية لفحص اتجاه النص مع English و <code>const answer = 42;</code> بدون تغيير ترتيب الشيفرة.</p><blockquote><p>يساعد التنظيم الواضح على متابعة الفكرة خطوة بخطوة.</p></blockquote><ul><li>قراءة المقدمة</li><li>تجربة المثال</li></ul><h2 id="rtl-next">الخطوة التالية</h2><p>تابع وفق سرعتك الخاصة مع صيغة <span class="katex">x = y + 1</span>.</p>';
$rendered['typography-rtl.html'] = render('singular.php');
$fixture_locale = 'zh_TW';
$rendered['typography-tw.html'] = render('singular.php');
$fixture_locale = 'en_US';
$fixture_fonts_enabled = true;
$font_samples = [
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
    '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
];
$font_available = false;
foreach ($font_samples as $path) {
    if (is_file($path)) {
        $dir = THEME . '/.runtime/frontend/assets/fonts';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        copy($path, $dir . '/fixture.ttf');
        $font_available = true;
        break;
    }
}
if ($font_available) {
    $posts[4001] = new WP_Post(4001, 'publish', 'wp_font_family');
    $posts[4001]->post_title = 'Fixture local font';
    $posts[4002] = new WP_Post(4002, 'publish', 'wp_font_face');
    $posts[4002]->post_parent = 4001;
    $posts[4002]->post_content = json_encode([
        'fontFamily' => 'Fixture local font',
        'src' => home_url('/assets/fonts/fixture.ttf'),
        'fontStyle' => 'normal',
        'fontWeight' => '400',
    ]);
    $mods = ['pagenest_font_body' => 4001, 'pagenest_font_heading' => 4001];
    $rendered['typography-font.html'] = render('singular.php');
    unset($posts[4001], $posts[4002]);
}
$fixture_fonts_enabled = false;
$mods = [];
[$posts[1001]->post_title, $posts[1001]->post_content] = $original;
$fixture_locale = 'zh_CN';
$rendered['typography-rtl-regions.html'] = str_replace(
    ['lang="zh-CN"', '继续探索这个专题', '如何使用这份导览？'],
    ['lang="ar" dir="rtl"', 'اكتشف المزيد من الموضوعات', 'كيف أستخدم هذا الدليل؟'],
    $rendered['regions-list.html'],
);
