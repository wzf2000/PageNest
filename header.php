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
    <a class="pagenest-skip" href="#pagenest-main">跳到主要内容</a>
    <header class="pagenest-header">
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
            <button class="pagenest-menu-toggle" type="button" aria-expanded="false"
                aria-controls="pagenest-nav">菜单 <span aria-hidden="true">☰</span></button>
            <nav id="pagenest-nav" aria-label="主导航"><?php
            $locations = get_nav_menu_locations();
            $items = wp_get_nav_menu_items($locations['pagenest_primary'] ?? 0) ?: [];
            if ($items) {
                pagenest_menu_branch($items);
            } else {
                 ?><a href="<?php echo esc_url(
    home_url('/'),
); ?>">首页</a><a href="<?php echo esc_url(pagenest_posts_url()); ?>">文章</a><?php
            }
            ?><a class="pagenest-account" href="<?php echo esc_url(
    is_user_logged_in() ? admin_url() : wp_login_url(),
); ?>"><?php echo is_user_logged_in() ? '我的账号' : '登录'; ?></a></nav>
        </div>
    </header>
