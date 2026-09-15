<?php
/** Legacy names are isolated here so existing installations retain their settings. */
defined('ABSPATH') || exit();
function pagenest_migrate_legacy_theme($old_name = '', $old_theme = null)
{
    if (get_option('stylesheet') !== 'pagenest') {
        return;
    }
    if (!$old_theme || $old_theme->get_stylesheet() !== 'wzf-journal') {
        return;
    }
    $old = get_option('theme_mods_wzf-journal', []);
    if (!is_array($old)) {
        return;
    }
    foreach ($old as $key => $value) {
        if ($key === 'nav_menu_locations' && is_array($value)) {
            if (isset($value['wzfl_primary'])) {
                $value['pagenest_primary'] = $value['wzfl_primary'];
                unset($value['wzfl_primary']);
            }
        }
        $key = preg_replace('/^wzfj_/', 'pagenest_', $key);
        set_theme_mod($key, $value);
    }
}
add_action('after_switch_theme', 'pagenest_migrate_legacy_theme', 100, 2);

function pagenest_experience_available()
{
    return class_exists('WZF_Experience') && WZF_Experience::ready();
}

add_action(
    'wp_enqueue_scripts',
    static function () {
        $manifest = require __DIR__ . '/assets/manifest.php';
        wp_enqueue_style(
            'pagenest-legacy',
            get_theme_file_uri('assets/' . $manifest['legacy']),
            ['pagenest'],
            null,
        );
        if (wp_script_is('pagenest-reading', 'enqueued')) {
            wp_add_inline_script(
                'pagenest-reading',
                "window.addEventListener('load',function(){var hash=decodeURIComponent(location.hash.slice(1));if(hash.indexOf('wzf-section-')===0){var target=document.getElementById(hash.replace('wzf-section-','pagenest-section-'));if(target)target.scrollIntoView();}});",
                'after',
            );
        }
    },
    100,
);
