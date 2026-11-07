<?php
/** The native account menu remains the single owner of signed-in account actions. */
defined('ABSPATH') || exit();
add_action(
    'admin_bar_menu',
    static function ($bar) {
        if (is_admin() || !is_user_logged_in()) {
            return;
        }
        ob_start();
        do_action('pagenest_account_actions', get_queried_object_id());
        $actions = trim(ob_get_clean());
        if ($actions !== '') {
            $bar->add_node([
                'id' => 'pagenest-account-reading',
                'parent' => 'user-actions',
                'title' => '<div class="pagenest-toolbar-reading">' . $actions . '</div>',
                'href' => false,
            ]);
        }
    },
    90,
);
