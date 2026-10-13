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

## 视觉与搜索接口

`pagenest_appearance_*` theme mods 由自定义器校验；`appearance.php` 是变量与默认值唯一入口，`assets/appearance.css` 提供无内联值时的默认值。不要编辑带哈希的资源。

`pagenest_search_endpoint` 可返回同源公开 GET 接口，参数 `q`、`kind`，响应为 `items` 数组（`id`、`title`、`url`、`kind_label`）；主题不发送身份凭据，拒绝跨源结果 URL。`pagenest_search_kinds` 扩展筛选名称，服务端必须自行执行相同的访问与范围检查。默认原生搜索不要求 Companion。

## 浏览编排与本地阅读偏好（1.2）

`browsing.php` 与 `home-sections.php` 负责首页模块和列表选项，不注册业务数据。模块键为 `topics/latest/authors/featured/updated/destinations`，开关和顺序分别为 `pagenest_home_{key}_enabled` / `pagenest_home_{key}_order`；精选／导览用 `pagenest_home_{key}_{0..3}` 引用原 post ID。每次渲染重新核对公开无密码、类型和去重，不以登录权限扩大推荐可见性。

`pagenest_card($id, $context = 'list')` 可传 `home` 保留首页卡片布局；列表预设为 `pagenest_list_style`，其余布尔项为 `pagenest_list_covers`、`pagenest_article_cover`、`pagenest_show_updated`、`pagenest_show_reading_time`。这些选项仅影响展示，不修改原文章／附件关系。

`reader-preferences.php` 在现有 `pagenest_reading_actions` 输出独立控件。脚本只移动自有节点，局部 data 属性和 CSS 变量不改变目录、正文锚点或评论槽协议。`pagenest-reader-preferences-v1` 本地存储只接受版本 1 与字号／宽度／配色白名单，无服务端同步；插件不可将其当作授权、身份或业务设置。

## 配置中心与样板（1.2）

`product.php` 注册原生 Appearance 配置页及 `pagenest` 区块样板分类。样板名称为 `pagenest/topic-introduction`、`pagenest/resource-cards` 和 `pagenest/article-outline`，只使用 core 区块；不绑定站点内容 ID。子主题或插件可通过 WordPress 原生样板 API 增加自己的样板。

视觉迁移格式固定为 `{"format":"pagenest-appearance","version":1,"settings":{}}`。白名单由 `pagenest_product_schema()` 从现有视觉定义派生，只含配色、background/text/accent 和六项数值。导入先完整校验，再单次更新当前 stylesheet 的 theme mods option；保留所有未知既有 theme mods，不运行重置或迁移。纯 JSON 校验和写入函数只用于已鉴权后台处理器，不是公开 API；扩展不得跳过权限与 nonce 检查。

资源诊断仅检查包内固定清单键，不接受请求提供的路径；要求哈希文件存在、文件名哈希匹配并与相应资源源文件一致。失败显示诊断状态，不自动修改资源。Companion 接口只通过 `function_exists()` 探测，不读取其私有配置。

主题翻译放在 `languages/`，PHP 与脚本均使用 WordPress 翻译机制；新增界面文案使用 `pagenest` 文本域并在输出处转义。不要翻译持久设置键、API 路径、事件名称或内容锚点。

### 主题翻译

界面使用英文 gettext 原文与 `pagenest` 文本域，`after_setup_theme` 加载 `languages/`，现有站长文案与正文不经过 gettext。小型浏览器脚本通过 `wp_localize_script` 接收翻译，不新增运行依赖。新增 PHP 文案用字面量 `__`／转义变体；计数用 `_n`，可变内容使用完整句子和编号占位符。主题 JSON 名称、归档模板名及主题描述也进入提取清单。

`npm run i18n` 提取 POT 并编译 `zh_CN.po` 为确定性 MO；`npm run i18n:check` 检查覆盖率、复数、占位符和已提交产物，已接入 `npm test`。修改 PHP 后需重新提取引用行号；新增原文需先在 PO 中补齐翻译。工具仅依赖 PHP 与 Python 标准库，不要求站点安装翻译插件。其他语言可使用标准 gettext 工具和 WordPress 语言包位置。
