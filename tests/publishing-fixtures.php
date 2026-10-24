<?php
/** Native listing presentation and footer contracts, included by verify-theme.php. */
foreach ([null, [], new stdClass(), false, 'grid-4', '<script>'] as $value) {
    check('invalid listing layout inherits', pagenest_listing_layout($value) === 'inherit');
    check('invalid sidebar inherits', pagenest_listing_sidebar($value) === 'inherit');
}
$mods = ['pagenest_list_style' => 'compact'];
check(
    'default inherits existing compact list',
    pagenest_listing_presentation() === ['layout' => 'compact', 'sidebar' => true],
);
foreach (['blog', 'archive', 'search'] as $context) {
    $fixture_archive = $context === 'archive';
    $fixture_search = $context === 'search';
    $fixture_search_results = true;
    check('native context ' . $context, pagenest_listing_context() === $context);
    foreach (['standard', 'compact', 'grid-2', 'grid-3'] as $layout) {
        $mods = [
            'pagenest_listing_' . $context . '_layout' => $layout,
            'pagenest_listing_' . $context . '_sidebar' => 'hide',
        ];
        check(
            'context layout and sidebar resolve',
            pagenest_listing_presentation() === ['layout' => $layout, 'sidebar' => false],
        );
        foreach (['blog', 'archive', 'search'] as $other) {
            if ($other !== $context) {
                $mods['pagenest_listing_' . $other . '_layout'] = 'compact';
                $mods['pagenest_listing_' . $other . '_sidebar'] = 'show';
            }
        }
        check(
            'other contexts do not override selection',
            pagenest_listing_presentation() === ['layout' => $layout, 'sidebar' => false],
        );
    }
}
$fixture_search = false;
$fixture_archive = false;
$mods = ['pagenest_listing_blog_layout' => 'grid-3', 'pagenest_listing_blog_sidebar' => 'hide'];
$fixture_404 = true;
check(
    '404 retains existing presentation',
    pagenest_listing_presentation() === ['layout' => 'standard', 'sidebar' => true],
);
$fixture_404 = false;
$mods = [];
function publishing_main($html)
{
    preg_match('/<main\b.*?<\/main>/s', $html, $matches);
    return preg_replace('/pagenest-search-[0-9]+/', 'pagenest-search-fixture', $matches[0]);
}
$default_listing = publishing_main(render('index.php'));
$default_home = publishing_main(render('front-page.php'));
$mods = ['pagenest_listing_blog_layout' => 'inherit', 'pagenest_listing_blog_sidebar' => 'inherit'];
check(
    'explicit defaults preserve listing markup',
    publishing_main(render('index.php')) === $default_listing,
);
$mods = ['pagenest_listing_blog_layout' => 'grid-3', 'pagenest_listing_blog_sidebar' => 'hide'];
check(
    'listing overrides preserve static homepage',
    publishing_main(render('front-page.php')) === $default_home,
);
$fixture_thumbnails = true;
$original_title = $posts[1001]->post_title;
$posts[1001]->post_title = str_repeat('从基础概念到实践：可持续阅读与发布的完整长标题', 3);
$fixture_footer_menu = true;
foreach (['blog', 'archive', 'search'] as $context) {
    $fixture_archive = $context === 'archive';
    $fixture_search = $context === 'search';
    foreach (['inherit', 'compact', 'grid-2', 'grid-3'] as $layout) {
        foreach (['show', 'hide'] as $sidebar) {
            $mods = [
                'pagenest_listing_' . $context . '_layout' => $layout,
                'pagenest_listing_' . $context . '_sidebar' => $sidebar,
            ];
            $query_count = count($fixture_queries);
            pagenest_listing_presentation();
            check('presentation does not add queries', count($fixture_queries) === $query_count);
            $html = render('index.php');
            check(
                'sidebar markup matches choice',
                str_contains($html, 'class="pagenest-sidebar"') === ($sidebar === 'show'),
            );
            ob_start();
            pagenest_card(1001);
            $card = ob_get_clean();
            $html = str_replace($card, str_repeat($card, 6), $html);
            check(
                'footer includes nested links and RSS',
                str_contains($html, '/deep') && str_contains($html, 'pagenest-feed'),
            );
            $rendered['publishing-' . $context . '-' . $layout . '-' . $sidebar . '.html'] = $html;
        }
    }
}
$fixture_footer_menu = false;
$fixture_search = false;
$fixture_archive = false;
$fixture_search_results = false;
$fixture_thumbnails = false;
$posts[1001]->post_title = $original_title;
$mods = [];
