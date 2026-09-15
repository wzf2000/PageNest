<form class="wzfl-search" role="search" method="get" action="<?php echo esc_url(
    home_url('/'),
); ?>"><label for="wzfj-search">搜索文章</label>
    <div><input id="wzfj-search" type="search" name="s" value="<?php echo esc_attr(
        get_search_query(),
    ); ?>" placeholder="标题、关键词…"><button type="submit">搜索</button></div>
</form>
