<?php
/** Included by the existing synthetic WordPress harness; no database or network. */
$mods = [];
check('home default order preserved', pagenest_home_order() === ['topics', 'latest', 'authors']);
foreach ([[], new stdClass(), false, '-5', '1<script>'] as $invalid) {
    check('invalid selection rejected', pagenest_browsing_id($invalid) === 0);
}
check(
    'checkbox double sanitization preserves true',
    pagenest_browsing_bool(pagenest_browsing_bool('1')),
);
check('unknown list style falls back', pagenest_list_style('bad') === 'standard');
$mods = [
    'pagenest_home_topics_enabled' => false,
    'pagenest_home_featured_enabled' => true,
    'pagenest_home_featured_order' => 0,
];
check(
    'module opt-in order and disable',
    pagenest_home_order() === ['featured', 'latest', 'authors'],
);
$mods['pagenest_home_latest_order'] = 0;
check(
    'equal positions use original order',
    pagenest_home_order() === ['latest', 'featured', 'authors'],
);
check(
    'hidden topics have no dead hero anchor',
    !str_contains(render('front-page.php'), 'href="#topics"'),
);
$mods = [
    'pagenest_home_featured_0' => 1001,
    'pagenest_home_featured_1' => 1003,
    'pagenest_home_featured_2' => 1004,
    'pagenest_home_featured_3' => 1005,
];
$allowed = [1003];
check(
    'curation excludes private password draft even for owner',
    array_column(pagenest_home_entries('featured'), 'ID') === [1001],
);
$mods['pagenest_home_featured_1'] = 1001;
$mods['pagenest_home_featured_2'] = 1006;
check(
    'curation deduplicates and rejects wrong type',
    array_column(pagenest_home_entries('featured'), 'ID') === [1001],
);
$mods['pagenest_home_featured_0'] = 1008;
check(
    'curation preserves configured order',
    array_column(pagenest_home_entries('featured'), 'ID') === [1008, 1001],
);
$posts[1008]->post_status = 'private';
check(
    'changed visibility rechecked without editing settings',
    array_column(pagenest_home_entries('featured'), 'ID') === [1001],
);
$posts[1008]->post_status = 'publish';
$mods = [];
$fixture_browsing = true;
$updated = pagenest_home_entries('updated');
check(
    'bounded modified query',
    end($fixture_queries)['orderby'] === ['modified' => 'DESC', 'ID' => 'DESC'] &&
        end($fixture_queries)['posts_per_page'] === 4 &&
        end($fixture_queries)['has_password'] === false,
);
check(
    'query filter cannot inject protected or wrong-type posts',
    array_column($updated, 'ID') === [1001, 1002, 1007, 1008],
);
$allowed = [];
$mods = [
    'pagenest_home_featured_enabled' => true,
    'pagenest_home_featured_order' => 0,
    'pagenest_home_featured_0' => 1001,
    'pagenest_home_featured_1' => 1002,
    'pagenest_home_updated_enabled' => true,
    'pagenest_home_updated_order' => 15,
    'pagenest_home_destinations_enabled' => true,
    'pagenest_home_destinations_order' => 18,
    'pagenest_home_destinations_0' => 1006,
    'pagenest_show_updated' => true,
    'pagenest_show_reading_time' => true,
    'pagenest_topics' => [
        [
            'title' => '技术阅读',
            'url' => home_url('/topic'),
            'description' => '从基础到实践，按自己的节奏阅读。',
            'status' => '持续更新',
        ],
    ],
];
$posts[1001]->post_title = '从基础概念到实践：开始阅读';
$posts[1002]->post_title = '设计可维护的内容系统';
$posts[1006]->post_title = '专题与系列导览';
$posts[1006]->post_excerpt = '按章节顺序阅读，探索系列内容。';
$fixture_thumbnails = true;
$rendered['home-composed.html'] = render('front-page.php');
check(
    'new modules use existing records',
    str_contains($rendered['home-composed.html'], 'data-pagenest-home-module="destinations"') &&
        str_contains($rendered['home-composed.html'], '持续更新'),
);
$mods['pagenest_topics'][0]['description'] = '<img src=x onerror=alert(1)>';
$mods['pagenest_topics'][0]['status'] = '<script>unsafe</script>';
$escaped = render('front-page.php');
check(
    'topic metadata escaped',
    str_contains($escaped, '&lt;img') &&
        str_contains($escaped, '&lt;script&gt;unsafe') &&
        !str_contains($escaped, '<script>unsafe'),
);
foreach (['standard', 'compact'] as $style) {
    $mods['pagenest_list_style'] = $style;
    ob_start();
    pagenest_card(1001);
    $card = ob_get_clean();
    check(
        'list preset ' . $style,
        str_contains($card, 'pagenest-entry-compact') === ($style === 'compact'),
    );
    $rendered['list-' . $style . '.html'] = str_replace(
        '<div class="pagenest-post-list">',
        '<div class="pagenest-post-list">' . $card . $card,
        render('index.php'),
    );
}
$mods['pagenest_list_covers'] = false;
ob_start();
pagenest_card(1001);
$card = ob_get_clean();
check(
    'disabled list cover omits image markup',
    !str_contains($card, 'pagenest-entry-image') && str_contains($card, 'pagenest-no-image'),
);
$mods['pagenest_article_cover'] = false;
$rendered['article-no-cover.html'] = render('singular.php');
check(
    'disabled article cover omits only cover',
    !str_contains($rendered['article-no-cover.html'], 'class="pagenest-cover"') &&
        str_contains($rendered['article-no-cover.html'], 'legacy-heading'),
);
$posts[1001]->post_password = 'secret';
ob_start();
pagenest_browsing_metadata(1001);
$metadata = ob_get_clean();
check('password metadata does not estimate protected content', $metadata === '');
ob_start();
pagenest_card(1001);
$protected = ob_get_clean();
check(
    'password card suppresses cover and body excerpt',
    !str_contains($protected, 'pagenest-entry-image') &&
        str_contains($protected, '这篇文章需要密码'),
);
$rendered['article-password.html'] = render('singular.php');
check(
    'password article omits reader preferences',
    !str_contains($rendered['article-password.html'], 'data-pagenest-reader-preferences'),
);
$posts[1001]->post_password = '';
$mods = [];
$fixture_thumbnails = false;
$fixture_browsing = false;
