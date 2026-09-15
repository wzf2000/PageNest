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
    <a class="wzfl-skip" href="#wzfl-main">跳到主要内容</a>
    <header class="wzfl-header">
        <div class="wzfl-nav-inner">
            <a class="wzfl-brand" href="<?php echo esc_url(home_url('/')); ?>"><?php
$logo = (int) get_theme_mod('custom_logo');
if ($logo) {
    echo wp_get_attachment_image($logo, [36, 36], false, ['class' => 'wzfl-logo', 'alt' => '']);
}
?><span><?php
$brand_parts = explode(' · ', get_bloginfo('name'), 2);
echo esc_html($brand_parts[0]);
if (isset($brand_parts[1])) { ?><span class="wzfl-brand-note"><?php echo esc_html(
    ' · ' . $brand_parts[1],
); ?></span><?php }
?></span></a>
            <button class="wzfl-menu-toggle" type="button" aria-expanded="false"
                aria-controls="wzfl-nav">菜单 <span aria-hidden="true">☰</span></button>
            <nav id="wzfl-nav" aria-label="主导航"><?php
            $locations = get_nav_menu_locations();
            $items = wp_get_nav_menu_items($locations['wzfl_primary'] ?? 0) ?: [];
            if ($items) {
                wzfj_menu_branch($items);
            } else {
                 ?><a href="<?php echo esc_url(
    home_url('/'),
); ?>">首页</a><a href="<?php echo esc_url(wzfj_posts_url()); ?>">文章</a><?php
            }
            ?><a class="wzfl-account" href="<?php echo esc_url(
    is_user_logged_in() ? admin_url() : wp_login_url(),
); ?>"><?php echo is_user_logged_in() ? '我的账号' : '登录'; ?></a></nav>
        </div>
    </header>
