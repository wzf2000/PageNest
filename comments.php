<?php
if (!defined('ABSPATH') || post_password_required()) {
    return;
} ?>
<header class="pagenest-discussion-title">
    <h2><?php echo get_comments_number()
        ? esc_html(
            sprintf(__('Comments (%s)', 'pagenest'), number_format_i18n(get_comments_number())),
        )
        : esc_html__('Discussion and comments', 'pagenest'); ?></h2><?php if (
    comments_open()
): ?><a href="#respond"><?php echo esc_html__(
    'Join the discussion →',
    'pagenest',
); ?></a><?php endif; ?>
</header>
<?php
if (have_comments()): ?><ol class="pagenest-comment-list"><?php wp_list_comments([
    'callback' => 'pagenest_comment',
    'style' => 'ol',
    'avatar_size' => 36,
    'short_ping' => true,
    'reply_text' => __('Reply', 'pagenest'),
    'max_depth' => get_option('thread_comments_depth'),
]); ?></ol><?php
$pages = paginate_comments_links([
    'prev_text' => __('← Previous page', 'pagenest'),
    'next_text' => __('Next page →', 'pagenest'),
    'echo' => false,
]);
if ($pages) {
    echo '<nav class="pagenest-pagination" aria-label="' .
        esc_attr__('Comment pagination', 'pagenest') .
        '">' .
        $pages .
        '</nav>';
}
endif;
if (comments_open()) {
    comment_form([
        'title_reply' => __('Post a comment', 'pagenest'),
        'title_reply_to' => __('Reply to %s', 'pagenest'),
        'cancel_reply_link' => __('Cancel reply', 'pagenest'),
        'label_submit' => __('Post a comment', 'pagenest'),
        'logged_in_as' =>
            '<p class="logged-in-as">' .
            get_avatar(get_current_user_id(), 36) .
            '<span>' .
            esc_html(
                sprintf(__('Commenting as %s', 'pagenest'), wp_get_current_user()->display_name),
            ) .
            '</span></p>',
        'class_form' => 'comment-form pagenest-comment-form',
        'class_submit' => 'pagenest-comment-submit',
        'comment_field' =>
            '<p class="comment-form-comment"><label for="comment">' .
            esc_html__('Comment', 'pagenest') .
            ' ' .
            '<span aria-hidden="true">*</span></label><textarea id="comment" name="comment" rows="5" placeholder="' .
            esc_attr__('Share your thoughts or describe a problem…', 'pagenest') .
            '" required></textarea></p>',
    ]);
} elseif (get_comments_number()) {
    echo '<p>' . esc_html__('New comments are closed for this article.', 'pagenest') . '</p>';
}
