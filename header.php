<!doctype html>
<html <?php language_attributes(); ?> class="no-js" data-pagenest-color-scheme="<?php echo esc_attr(
     pagenest_color_scheme(get_theme_mod('pagenest_color_scheme', 'light')),
 ); ?>">
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
    <header class="pagenest-header<?php echo pagenest_header_layout(
        get_theme_mod('pagenest_header_layout', 'standard'),
    ) === 'two-row'
        ? ' pagenest-header-two-row'
        : ''; ?>" data-mbb-teaching-sticky-obstruction>
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
?>
                <?php
                $account_label = is_user_logged_in()
                    ? __('My account', 'pagenest')
                    : __('Log in', 'pagenest');
                $account_url = is_user_logged_in() ? admin_url() : wp_login_url();
                ob_start();
                do_action('pagenest_account_actions', get_queried_object_id());
                $account_actions = trim(ob_get_clean());
                if ($account_actions !== '') { ?>
                <details class="pagenest-account-menu pagenest-dropdown">
                    <summary><?php echo esc_html__('My space', 'pagenest'); ?></summary>
                    <div class="pagenest-dropdown-links"><a class="pagenest-account" href="<?php echo esc_url(
                        $account_url,
                    ); ?>"><?php echo esc_html(
    $account_label,
); ?></a><?php echo $account_actions; ?></div>
                </details>
                <?php } else { ?>
                <a class="pagenest-account pagenest-account-direct" href="<?php echo esc_url(
                    $account_url,
                ); ?>" aria-label="<?php echo esc_attr($account_label); ?>"><?php echo esc_html(
    $account_label,
); ?></a>
                <?php }
                ?>
            </nav>
            <details class="pagenest-header-search">
                <summary aria-label="<?php echo esc_attr__(
                    'Search',
                    'pagenest',
                ); ?>"><?php echo pagenest_ui_icon(
    'search',
); ?><span class="screen-reader-text"><?php echo esc_html__(
    'Search',
    'pagenest',
); ?></span></summary>
                <div class="pagenest-search-popover"><?php get_search_form(); ?></div>
            </details>
            <div class="pagenest-header-actions" data-pagenest-header-actions><?php do_action(
                'pagenest_header_actions',
                get_queried_object_id(),
            ); ?></div>

        </div>
    </header>
    <?php pagenest_render_region('after_header'); ?>
