# 扩展接口

面向主题扩展开发者；后台配置步骤见 [使用指南](USAGE.md)。

## 自定义器设置键

以下为新增设置键；既有设置键保留。

| 设置键                                                          | 用途                           |
| --------------------------------------------------------------- | ------------------------------ |
| `pagenest_home_eyebrow`                                         | 首页眉题                       |
| `pagenest_home_intro`                                           | 首页介绍                       |
| `pagenest_authors_intro`                                        | 作者介绍                       |
| `pagenest_footer_tagline`                                       | 页脚标语                       |
| `pagenest_community_description`                                | 交流区说明                     |
| `pagenest_community_link_label` / `pagenest_community_link_url` | 交流链接名称与地址             |
| `pagenest_community_archive_label`                              | 交流区归档链接名称             |
| `pagenest_archive_link_label` / `pagenest_archive_link_url`     | 页脚归档链接名称与共用归档地址 |
| `pagenest_about_link_label` / `pagenest_about_link_url`         | 关于链接名称与地址             |

## 阅读与文章钩子

主题默认显示同分类的公开相关文章。扩展使用以下 WordPress 钩子接入自己的系列导航或文章操作：

- `pagenest_related_posts($posts, $post_id)` filter：返回文章对象或 ID 数组；主题在过滤后排除当前文章、重复、非文章、尚未通过密码验证及无权读取的记录，最多显示三篇。仅公开文章和有权限的私密文章可显示。
- `pagenest_related_title($title, $post_id, $posts)` filter：设置相关文章标题，输出前统一转义。
- `pagenest_article_actions($post_id)` action：在未被密码拦截的文章页脚输出扩展按钮；扩展自行负责鉴权、脚本、状态与输出转义。

扩展顶栏按钮添加 `data-pagenest-header-action` 属性，主题初始化或收到 document 上的
`pagenest-integration-ready` 事件后，按 DOM 顺序将它们放在顶栏菜单按钮前。
属性值由扩展自行定义，事件无需 detail，可反复触发。
现有 `.pagenest-notes-slot` 和 `pagenest-reading-layout` 事件供阅读扩展复用；主题不读取笔记数据。
主题提供 `pagenest_header_actions`、`pagenest_reading_actions` 动作及 `[data-pagenest-header-actions]`、`[data-pagenest-reading-panel]` DOM 位置。扩展可直接注册入口；没有配套插件时布局和目录正常工作。正文、表格滚动和题目提示分别使用 `pagenest-article-body`、`pagenest-table-scroll`、`pagenest-exercise-hint`；旧标识映射由配套插件的外部配置负责。

`pagenest-reading-layout` 在阅读侧栏初始化与尺寸更新后于 document 上派发，无需 detail。扩展可监听它重新计算自己的布局；`.pagenest-notes-slot` 是默认隐藏的接入槽，扩展自行负责数据、权限和显示状态。

主题声明 `pagenest-independent-layout` 特性，兼容插件据此启用相应前端适配。

> [使用指南](USAGE.md) · [贡献指南](../CONTRIBUTING.md) · [返回项目首页](../README.md)
