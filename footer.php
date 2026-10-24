<?php pagenest_render_region('before_footer'); ?>
<footer class="pagenest-footer<?php
echo has_nav_menu('pagenest_footer') ? ' pagenest-footer-with-menu' : '';
echo pagenest_footer_layout(get_theme_mod('pagenest_footer_layout', 'standard')) === 'stacked'
    ? ' pagenest-footer-stacked'
    : '';
?>">
    <div><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
        <?php if ($tagline = pagenest_setting('pagenest_footer_tagline')): ?>
        <p><?php echo esc_html($tagline); ?></p>
        <?php endif; ?>
    </div>
    <div><?php if (has_nav_menu('pagenest_footer')) {
        wp_nav_menu([
            'theme_location' => 'pagenest_footer',
            'container' => 'nav',
            'container_class' => 'pagenest-footer-nav',
            'container_aria_label' => __('Footer navigation', 'pagenest'),
            'menu_class' => 'pagenest-footer-menu',
            'fallback_cb' => false,
            'depth' => 0,
        ]);
    } else {
        foreach (['archive', 'about'] as $link):
            $label = pagenest_setting('pagenest_' . $link . '_link_label');
            $url = pagenest_setting('pagenest_' . $link . '_link_url');
            if ($label !== '' && esc_url($url) !== ''): ?>
        <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
        <?php endif;
        endforeach;
    } ?><span>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(
     get_theme_mod('pagenest_footer_owner', get_bloginfo('name')),
 ); ?></span>
        <?php
        $record = get_theme_mod('pagenest_footer_record', '');
        if ($record !== ''): ?>
        <a href="https://beian.miit.gov.cn/" rel="external"><?php echo esc_html($record); ?></a>
        <?php endif;
        ?><small><?php echo esc_html__('Powered by WordPress', 'pagenest'); ?></small>
    </div>
    <p class="pagenest-feed"><a href="<?php echo esc_url(
        get_feed_link(),
    ); ?>"><?php echo esc_html__('Subscribe via RSS', 'pagenest'); ?></a></p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
