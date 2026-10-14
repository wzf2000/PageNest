<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.classList.replace('no-js','js');</script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <a class="pagenest-skip" href="#pagenest-main"><?php echo esc_html__(
        'Skip to main content',
        'pagenest',
    ); ?></a>
    <header class="pagenest-header" data-mbb-teaching-sticky-obstruction>
        <div class="pagenest-nav-inner">
            <a class="pagenest-brand" href="<?php echo esc_url(home_url('/')); ?>"><?php
$logo = (int) get_theme_mod('custom_logo');
if ($logo) {
    echo wp_get_attachment_image($logo, [36, 36], false, ['class' => 'pagenest-logo', 'alt' => '']);
}
?><span><?php
$brand_parts = explode(' · ', get_bloginfo('name'), 2);
echo esc_html($brand_parts[0]);
if (isset($brand_parts[1])) { ?><span class="pagenest-brand-note"><?php echo esc_html(
    ' · ' . $brand_parts[1],
); ?></span><?php }
?></span></a>
            <div class="pagenest-header-actions" data-pagenest-header-actions><?php do_action(
                'pagenest_header_actions',
                get_queried_object_id(),
            ); ?></div>
            <details class="pagenest-header-search">
                <summary><?php echo esc_html__('Search', 'pagenest'); ?></summary>
                <div class="pagenest-search-popover"><?php
                get_search_form();
                pagenest_discovery_links();
                ?></div>
            </details>
            <button class="pagenest-menu-toggle" type="button" aria-expanded="false"
                aria-controls="pagenest-nav"><?php echo esc_html__(
                    'Menu',
                    'pagenest',
                ); ?> <span aria-hidden="true">☰</span></button>
            <nav id="pagenest-nav" aria-label="<?php echo esc_attr__(
                'Primary navigation',
                'pagenest',
            ); ?>"><?php
$locations = get_nav_menu_locations();
$items = wp_get_nav_menu_items($locations['pagenest_primary'] ?? 0) ?: [];
if ($items) {
    pagenest_menu_branch($items);
} else {
     ?><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html__(
    'Home',
    'pagenest',
); ?></a><a href="<?php echo esc_url(pagenest_posts_url()); ?>"><?php echo esc_html__(
    'Articles',
    'pagenest',
); ?></a><?php
}
?><a class="pagenest-account" href="<?php echo esc_url(
    is_user_logged_in() ? admin_url() : wp_login_url(),
); ?>"><?php echo esc_html(
    is_user_logged_in() ? __('My account', 'pagenest') : __('Log in', 'pagenest'),
); ?></a></nav>
        </div>
    </header>
