<?php
if (!defined('ABSPATH') || post_password_required()) {
    return;
} ?>
<header class="wzfl-discussion-title">
    <h2><?php echo get_comments_number()
        ? '评论（' . esc_html(get_comments_number()) . '）'
        : '交流与评论'; ?></h2><?php if (
    comments_open()
): ?><a href="#respond">参与讨论 →</a><?php endif; ?>
</header>
<?php
if (have_comments()): ?><ol class="wzfl-comment-list"><?php wp_list_comments([
    'callback' => 'wzfj_comment',
    'style' => 'ol',
    'avatar_size' => 36,
    'short_ping' => true,
    'reply_text' => '回复',
    'max_depth' => get_option('thread_comments_depth'),
]); ?></ol><?php
$pages = paginate_comments_links([
    'prev_text' => '← 上一页',
    'next_text' => '下一页 →',
    'echo' => false,
]);
if ($pages) {
    echo '<nav class="wzfl-pagination" aria-label="评论分页">' . $pages . '</nav>';
}
endif;
if (comments_open()) {
    comment_form([
        'title_reply' => '发表评论',
        'title_reply_to' => '回复 %s',
        'cancel_reply_link' => '取消回复',
        'label_submit' => '发表评论',
        'logged_in_as' =>
            '<p class="logged-in-as">' .
            get_avatar(get_current_user_id(), 36) .
            '<span>以 ' .
            esc_html(wp_get_current_user()->display_name) .
            ' 的身份发言</span></p>',
        'class_form' => 'comment-form wzfl-comment-form',
        'class_submit' => 'wzfl-comment-submit',
        'comment_field' =>
            '<p class="comment-form-comment"><label for="comment">评论内容 <span aria-hidden="true">*</span></label><textarea id="comment" name="comment" rows="5" placeholder="分享你的想法，或描述遇到的问题…" required></textarea></p>',
    ]);
} elseif (get_comments_number()) {
    echo '<p>这篇文章已关闭新评论。</p>';
}
