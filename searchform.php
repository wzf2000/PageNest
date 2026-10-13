<?php
$id = wp_unique_id('pagenest-search-');
$endpoint = apply_filters('pagenest_search_endpoint', '');
?>
<form class="pagenest-search" role="search" method="get" action="<?php echo esc_url(
    home_url('/'),
); ?>" <?php if ($endpoint): ?>data-pagenest-search-endpoint="<?php echo esc_url(
    $endpoint,
); ?>" <?php endif; ?>>
    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html__(
    'Search this site',
    'pagenest',
); ?></label>
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
    <div class="pagenest-search-filters"><label for="<?php echo esc_attr(
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
    <p class="pagenest-search-status" role="status" aria-live="polite"></p>
    <ul class="pagenest-search-suggestions" aria-label="<?php echo esc_attr__(
        'Search suggestions',
        'pagenest',
    ); ?>" hidden></ul>
</form>
