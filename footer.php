<footer class="pagenest-footer">
    <div><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>
        <p>记录技术、学习与生活</p>
    </div>
    <div><a href="<?php echo esc_url(
        home_url('/archives/'),
    ); ?>">归档</a><a href="<?php echo esc_url(
    home_url('/') . '#authors',
); ?>">关于</a><span>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(
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
