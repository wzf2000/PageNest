<?php
/** Front-end chrome uses progressive enhancement; existing controls remain without scripts. */
defined('ABSPATH') || exit();
add_action(
    'wp_enqueue_scripts',
    static function () {
        $assets = require __DIR__ . '/assets/manifest.php';
        wp_enqueue_script(
            'pagenest-overlays',
            get_theme_file_uri('assets/' . $assets['overlays_js']),
            [],
            null,
            true,
        );
        wp_enqueue_style(
            'pagenest-header-reading',
            get_theme_file_uri('assets/' . $assets['header_reading_css']),
            ['pagenest-editorial', 'pagenest-presentation'],
            null,
        );
        wp_enqueue_style(
            'pagenest-reading-tools',
            get_theme_file_uri('assets/' . $assets['reading_tools_css']),
            ['pagenest-header-reading'],
            null,
        );
        wp_enqueue_script(
            'pagenest-header-reading',
            get_theme_file_uri('assets/' . $assets['header_reading_js']),
            ['pagenest', 'pagenest-overlays'],
            null,
            true,
        );
        wp_localize_script('pagenest-header-reading', 'pagenestHeaderL10n', [
            'account' => __('My space', 'pagenest'),
            'close' => __('Close', 'pagenest'),
            'show' => __('Show navigation', 'pagenest'),
            'siteTools' => __('Site tools', 'pagenest'),
        ]);
        if (!is_singular('post') || post_password_required()) {
            return;
        }
        wp_enqueue_script(
            'pagenest-reading-tools',
            get_theme_file_uri('assets/' . $assets['reading_tools_js']),
            ['pagenest-reading', 'pagenest-reader-preferences', 'pagenest-overlays'],
            null,
            true,
        );
        wp_localize_script('pagenest-reading-tools', 'pagenestReadingToolsL10n', [
            'contents' => __('Contents', 'pagenest'),
            'discussion' => __('Discussion', 'pagenest'),
            'more' => __('Reading and more', 'pagenest'),
            'settings' => __('Reading settings', 'pagenest'),
            'share' => __('Share article', 'pagenest'),
            'close' => __('Close', 'pagenest'),
        ]);
    },
    30,
);
