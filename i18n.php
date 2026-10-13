<?php
/** Native gettext strings shared with the small, dependency-free front-end scripts. */
defined('ABSPATH') || exit();

function pagenest_comment_count($count): string
{
    return sprintf(
        _n('%s comment', '%s comments', (int) $count, 'pagenest'),
        number_format_i18n($count),
    );
}

function pagenest_script_translations(): array
{
    return [
        'pagenest-content' => ['tableLabel' => __('Horizontally scrollable table', 'pagenest')],
        'pagenest-reading' => [
            'uploadImage' => __('Upload image', 'pagenest'),
            'body' => __('Article body', 'pagenest'),
            /* translators: %s is a heading in the table of contents. */
            'toggleHeading' => __('Expand or collapse: %s', 'pagenest'),
            'expandAll' => __('Expand all', 'pagenest'),
            'collapseAll' => __('Collapse all', 'pagenest'),
            'currentPosition' => __('Contains the current reading position', 'pagenest'),
        ],
        'pagenest-discovery' => [
            'loading' => __('Searching…', 'pagenest'),
            /* translators: %s is the number of suggestions, up to eight. */
            'results' => __('Suggestions: %s. Submit your search for all results.', 'pagenest'),
            'empty' => __(
                'No suggestions found. Try another keyword or submit your search.',
                'pagenest',
            ),
            'unavailable' => __(
                'Suggestions are unavailable. Press Enter to continue searching.',
                'pagenest',
            ),
        ],
        'pagenest-reader-preferences' => [
            'reset' => __('Site defaults restored.', 'pagenest'),
            'saved' => __('Reading preferences saved.', 'pagenest'),
            'resetUnsaved' => __(
                'Site defaults restored; browser storage is unavailable.',
                'pagenest',
            ),
            'unsaved' => __('Applied to this page; browser storage is unavailable.', 'pagenest'),
        ],
    ];
}

function pagenest_localize_scripts(): void
{
    $names = [
        'pagenest-content' => 'pagenestContentL10n',
        'pagenest-reading' => 'pagenestReadingL10n',
        'pagenest-discovery' => 'pagenestDiscoveryL10n',
        'pagenest-reader-preferences' => 'pagenestReaderL10n',
    ];
    foreach (pagenest_script_translations() as $handle => $strings) {
        if (wp_script_is($handle, 'enqueued')) {
            wp_localize_script($handle, $names[$handle], $strings);
        }
        if (
            $handle === 'pagenest-content' &&
            wp_script_is('pagenest-content-preview', 'enqueued')
        ) {
            wp_localize_script('pagenest-content-preview', $names[$handle], $strings);
        }
    }
}
add_action('wp_enqueue_scripts', 'pagenest_localize_scripts', 100);
add_action('admin_enqueue_scripts', 'pagenest_localize_scripts', 100);
