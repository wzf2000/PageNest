<footer class="pagenest-footer">
    <div><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
        <?php if ($tagline = pagenest_setting('pagenest_footer_tagline')): ?>
        <p><?php echo esc_html($tagline); ?></p>
        <?php endif; ?>
    </div>
    <div><?php foreach (['archive', 'about'] as $link):
        $label = pagenest_setting('pagenest_' . $link . '_link_label');
        $url = pagenest_setting('pagenest_' . $link . '_link_url');
        if ($label !== '' && esc_url($url) !== ''): ?>
        <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
        <?php endif;
    endforeach; ?><span>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(
     get_theme_mod('pagenest_footer_owner', get_bloginfo('name')),
 ); ?></span>
        <?php
        $record = get_theme_mod('pagenest_footer_record', '');
        if ($record !== ''): ?>
        <a href="https://beian.miit.gov.cn/" rel="external"><?php echo esc_html($record); ?></a>
        <?php endif;
        ?><small>由
            WordPress 支持</small>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
