<?php
if (!defined('ABSPATH')) {
    exit();
}
require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/reading.php';
add_action('after_setup_theme', static function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height' => 36,
        'width' => 36,
        'flex-height' => true,
        'flex-width' => true,
    ]);
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    add_theme_support('wzf-independent-layout');
    register_nav_menu('wzfl_primary', '全站主导航');
});
add_filter('body_class', static function ($classes) {
    $classes[] = 'wzfl-site';
    return $classes;
});
add_action('wp_enqueue_scripts', static function () {
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
    $manifest = require __DIR__ . '/assets/manifest.php';
    wp_enqueue_style('wzf-journal', get_theme_file_uri('assets/' . $manifest['css']), [], null);
    wp_enqueue_script(
        'wzf-journal',
        get_theme_file_uri('assets/' . $manifest['js']),
        [],
        null,
        true,
    );
    $image = wp_get_attachment_image_url((int) get_theme_mod('wzfj_hero_attachment', 0), 'full');
    if ($image) {
        wp_add_inline_style(
            'wzf-journal',
            ':root{--wzfj-hero-image:url("' . esc_url_raw($image) . '")}',
        );
    }
});
// Prevent logged-in variants being reused as an anonymous page.
add_action('template_redirect', static function () {
    if (is_user_logged_in()) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();
    }
});

add_action('wp_enqueue_scripts', static function () {
    if (is_singular()) {
        $manifest = require __DIR__ . '/assets/manifest.php';
        wp_enqueue_script(
            'wzfj-reading',
            get_theme_file_uri('assets/' . $manifest['reading']),
            [],
            null,
            true,
        );
    }
});
// Use the horizontal widget wherever it fits, compact only in narrow forms.
add_filter(
    'comment_form_submit_button',
    static function ($html) {
        return preg_replace_callback(
            '/<span\b[^>]*\bclass="[^"]*cf-turnstile-comments[^"]*"[^>]*><\/span>/i',
            static function ($m) {
                $widget = preg_replace('/\bdata-size="[^"]*"/', 'data-size="normal"', $m[0]);
                return $widget .
                    '<script>(function(){var el=document.currentScript.previousElementSibling;if(el&&el.parentElement.clientWidth<300)el.dataset.size="compact";})();</script>';
            },
            $html,
        );
    },
    110,
);

require_once __DIR__ . '/customizer.php';

require_once __DIR__ . '/login.php';
