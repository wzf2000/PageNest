<?php
$id = wp_unique_id('pagenest-search-');
$endpoint = apply_filters('pagenest_search_endpoint', '');
$series = apply_filters('pagenest_search_series', []);
?>
<form class="pagenest-search<?php echo $series
    ? ' pagenest-search-has-series'
    : ''; ?>" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" <?php if (
    $endpoint
): ?>data-pagenest-search-endpoint="<?php echo esc_url($endpoint); ?>" <?php endif; ?>>
    <div class="pagenest-search-query"><label for="<?php echo esc_attr(
        $id,
    ); ?>"><?php echo esc_html__('Search this site', 'pagenest'); ?></label>
        <div class="pagenest-search-input-row"><input id="<?php echo esc_attr(
            $id,
        ); ?>" type="search" name="s" value="<?php echo esc_attr(
    get_search_query(false),
); ?>" placeholder="<?php echo esc_attr__(
    'Title, keywords…',
    'pagenest',
); ?>" maxlength="100" autocomplete="off"><button type="submit"><?php echo esc_html__(
    'Search',
    'pagenest',
); ?></button></div>
    </div>
    <div class="pagenest-search-filters">
        <div class="pagenest-search-field"><label for="<?php echo esc_attr(
            $id,
        ); ?>-kind"><?php echo esc_html__(
    'Content type',
    'pagenest',
); ?></label><select id="<?php echo esc_attr($id); ?>-kind" name="pn_kind"><?php foreach (
    pagenest_search_kinds()
    as $value => $label
): ?><option value="<?php echo esc_attr($value); ?>" <?php selected(
    pagenest_search_kind(),
    $value,
); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></div>
        <div class="pagenest-search-field">
            <label for="<?php echo esc_attr($id); ?>-category"><?php echo esc_html__(
    'Category',
    'pagenest',
); ?></label>
            <select id="<?php echo esc_attr($id); ?>-category" name="pn_category">
                <option value="0"><?php echo esc_html__(
                    'All categories',
                    'pagenest',
                ); ?></option><?php foreach (
    get_categories(['hide_empty' => true])
    as $category
): ?><option value="<?php echo esc_attr($category->term_id); ?>" <?php selected(
    pagenest_search_param('pn_category'),
    $category->term_id,
); ?>><?php echo esc_html($category->name); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="pagenest-search-field">
            <label for="<?php echo esc_attr($id); ?>-sort"><?php echo esc_html__(
    'Sort',
    'pagenest',
); ?></label>
            <select id="<?php echo esc_attr($id); ?>-sort" name="pn_sort">
                <option value="relevance"><?php echo esc_html__(
                    'Relevance',
                    'pagenest',
                ); ?></option>
                <option value="modified" <?php selected(
                    pagenest_search_param('pn_sort'),
                    'modified',
                ); ?>><?php echo esc_html__('Recently updated', 'pagenest'); ?></option>
            </select>
        </div>
        <?php if ($series): ?><div class="pagenest-search-field"><label for="<?php echo esc_attr(
    $id,
); ?>-series"><?php echo esc_html__('Series', 'pagenest'); ?></label><input id="<?php echo esc_attr(
    $id,
); ?>-series" name="pn_series" value="<?php echo esc_attr(
    pagenest_search_param('pn_series'),
); ?>" maxlength="100" list="<?php echo esc_attr(
    $id,
); ?>-series-list"><datalist id="<?php echo esc_attr($id); ?>-series-list"><?php foreach (
    $series
    as $value
): ?><option value="<?php echo esc_attr(
    $value,
); ?>"><?php endforeach; ?></datalist></div><?php endif; ?>
    </div>
    <p class="pagenest-search-status" role="status" aria-live="polite"></p>
    <ul class="pagenest-search-suggestions" aria-label="<?php echo esc_attr__(
        'Search suggestions',
        'pagenest',
    ); ?>" hidden></ul>
</form>
