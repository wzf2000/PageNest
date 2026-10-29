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
主题提供 `pagenest_header_actions`、`pagenest_reading_actions` 动作及 `[data-pagenest-header-actions]`、`[data-pagenest-reading-panel]` DOM 位置。账号菜单另提供 `pagenest_account_actions` 动作与 `pagenest-account-actions` 主题特性；扩展确认该特性后可将个人入口放入账号菜单，在旧主题继续使用原页眉动作。账号菜单保留原登录或后台链接；没有额外入口时显示直接链接。扩展可直接注册入口；没有配套插件时布局和目录正常工作。正文、表格滚动和题目提示分别使用 `pagenest-article-body`、`pagenest-table-scroll`、`pagenest-exercise-hint`；旧标识映射由配套插件的外部配置负责。

`pagenest-reading-layout` 在阅读侧栏初始化与尺寸更新后于 document 上派发，无需 detail。扩展可监听它重新计算自己的布局；`.pagenest-notes-slot` 是默认隐藏的接入槽，扩展自行负责数据、权限和显示状态。

主题声明 `pagenest-independent-layout` 特性，兼容插件据此启用相应前端适配。

教学内容的目录保留隐藏阶段与折叠内部的作者标题；点击目录或打开深链接时，若存在 `window.MarkBridgeTeaching.revealTarget(target)`，先同步展开目标所属内容，再滚动定位。扩展完成阶段或折叠切换后可派发 document 事件 `mbb:teaching-change`，目录据此重新计算当前可见标题。主题不克隆或删除作者正文，也不记录学习进度。标记 `data-mbb-generated` 或 `data-pagenest-generated` 的展示控件不进入目录。

> [使用指南](USAGE.md) · [贡献指南](../CONTRIBUTING.md) · [返回项目首页](../README.md)

## 视觉与搜索接口

`pagenest_appearance_*` theme mods 由自定义器校验；`appearance.php` 是变量与默认值唯一入口，`assets/appearance.css` 提供无内联值时的默认值。不要编辑带哈希的资源。

`pagenest_search_endpoint` 可返回同源公开 GET 接口，参数 `q`、`kind`，响应为 `items` 数组（`id`、`title`、`url`、`kind_label`）；主题不发送身份凭据，拒绝跨源结果 URL。`pagenest_search_kinds` 扩展筛选名称，服务端必须自行执行相同的访问与范围检查。默认原生搜索不要求 Companion。

## 浏览编排与本地阅读偏好（1.2）

`browsing.php` 与 `home-sections.php` 负责首页模块和列表选项，不注册业务数据。模块键为 `topics/latest/authors/featured/updated/destinations`，开关和顺序分别为 `pagenest_home_{key}_enabled` / `pagenest_home_{key}_order`；精选／导览用 `pagenest_home_{key}_{0..3}` 引用原 post ID。每次渲染重新核对公开无密码、类型和去重，不以登录权限扩大推荐可见性。

`pagenest_card($id, $context = 'list')` 可传 `home` 保留首页卡片布局；列表预设为 `pagenest_list_style`，其余布尔项为 `pagenest_list_covers`、`pagenest_article_cover`、`pagenest_show_updated`、`pagenest_show_reading_time`。这些选项仅影响展示，不修改原文章／附件关系。

`reader-preferences.php` 在现有 `pagenest_reading_actions` 输出独立控件。脚本只移动自有节点，局部 data 属性和 CSS 变量不改变目录、正文锚点或评论槽协议。`pagenest-reader-preferences-v1` 本地存储只接受版本 1 与字号／宽度／配色白名单，主题自身无服务端同步；插件不可将其当作授权、身份或业务设置。

原生博客、归档和搜索列表由 `pagenest_listing_presentation()` 解析。`pagenest_listing_{blog|archive|search}_layout` 仅接受 `inherit/standard/compact/grid-2/grid-3`，默认与非法值均为 `inherit`，继承 `pagenest_list_style`；`pagenest_listing_{blog|archive|search}_sidebar` 仅接受 `inherit/show/hide`，默认与非法值均为 `inherit`，保留原来的侧栏。仅在原生对应上下文应用，404 与静态页面不读取这些覆盖。设置及全局列表样式加入版本 1 视觉 JSON 白名单；导入非法值整包拒绝，遗漏值保留。菜单位置、内容与用户数据不在白名单内。

分类专属覆盖保存在 term meta `_pagenest_listing`，值为 `['layout' => 'inherit|standard|compact|grid-2|grid-3', 'sidebar' => 'inherit|show|hide']`。`category_edit_form_fields` 展示原生编辑字段，`edited_category` 保存时核对目标 taxonomy、`edit_term` 能力与绑定 term ID 的 nonce；缺字段或非法值不写入，双继承删除本项 meta。布局和侧栏分别按「当前分类显式值 → 全局 archive 值 → 旧列表样式／默认侧栏」解析。仅当前 `is_category()` 的 queried ID 生效，不遍历父分类，不影响 tag 或其他 term meta；不注册 REST 写入、不加入全站视觉 JSON。

`pagenest_footer` 是独立原生菜单位置；分配后替换页脚两个快捷链接，`wp_nav_menu` 禁用自动页面回退并保留全部深度。未分配继续使用原快捷链接。菜单使用原生链接与列表，不要求脚本或额外 walker，主导航位置不变。

### 阅读偏好事件协议（1.8）

1.8.0 起提供以下版本 1 协议。主题无网络请求，账号身份、授权、远程存储与撤回由扩展负责。所有事件在 `document` 上派发，事件名以 `pagenest-reader-preferences-` 开头。

| 事件      | 用途与 `detail`                                                                                                    |
| --------- | ------------------------------------------------------------------------------------------------------------------ |
| `request` | 扩展握手：`{version:1,requestId?}`；主题即使已完成初始化也会返回 `ready`                                           |
| `ready`   | 主题可用：`{version:1,source,changed:[],preferences,requestId}`                                                    |
| `change`  | 读者操作：`{version:1,source:'user',changed,preferences,requestId:''}`；`changed` 只列实际改动字段，恢复默认列三项 |
| `apply`   | 扩展应用：`{version:1,source,changed,preferences,requestId}`；`source` 为 `remote` 或 `local`                      |
| `applied` | 应用完成：沿用版本、来源、改动字段与请求编号，并返回实际显示的 `preferences`                                       |

`requestId` 为最长 100 字符的字符串。`changed` 是不重复、最多三项的白名单：`size`（`default/18/20/22`）、`width`（`default/compact/wide`）、`palette`（`default/paper/dark`）。`apply` 的 `source:remote` 用 `changed` 指定的有效字段建立本页覆盖，其余使用本机值；每次应用替换上一份远程覆盖。`source:local` 清除覆盖，扩展可传空 `changed` 与空 `preferences`。

远程应用仅更新显示，不写 `pagenest-reader-preferences-v1`，也不派发用户 `change`。远程覆盖期间用户改动一项时，只改写本机这一项，保留本机其他字段；`change.preferences` 是当前显示值，扩展只能上传 `changed` 指定字段，不能把整份显示值当成本机改动。扩展负责在隐藏、离开页面或认证失效时应用 `source:local`，并校验自己的响应代数，防止迟到结果恢复旧覆盖。`ready/request` 握手避免双方脚本加载顺序造成漏接。

## 配置中心与样板（1.2）

`product.php` 注册原生 Appearance 配置页及 `pagenest` 区块样板分类。保留 `pagenest/topic-introduction`、`pagenest/resource-cards` 和 `pagenest/article-outline`，追加 `pagenest/topic-groups`、`pagenest/reading-steps`、`pagenest/faq`。所有样板只使用静态可编辑 core 区块，不绑定站点内容 ID、资源或查询。新标题使用 `wp-block-heading`，FAQ 使用 `core/details`，需要 WordPress 6.3+ 且区块已注册，能力不可用时只略过 FAQ 样板和预览；不改写已经保存的正文。专题页沿用既有 `_pagenest_layout` 的 topic context 和 standard/overview/wide 预设，不增加平行设置。子主题或插件可通过 WordPress 原生样板 API 增加自己的样板。

视觉迁移格式固定为 `{"format":"pagenest-appearance","version":1,"settings":{}}`。白名单由 `pagenest_product_schema()` 从现有视觉定义派生，包含配色、background/text/accent、六项数值、全站明暗与既有文章／专题／工具布局，以及全局列表样式和下述列表展示枚举。导入先完整校验，再单次更新当前 stylesheet 的 theme mods option；保留所有未知既有 theme mods，不运行重置或迁移。纯 JSON 校验和写入函数只用于已鉴权后台处理器，不是公开 API；扩展不得跳过权限与 nonce 检查。

资源诊断仅检查包内固定清单键，不接受请求提供的路径；要求哈希文件存在、文件名哈希匹配并与相应资源源文件一致。失败显示诊断状态，不自动修改资源。Companion 接口只通过 `function_exists()` 探测，不读取其私有配置。

主题翻译放在 `languages/`，PHP 与脚本均使用 WordPress 翻译机制；新增界面文案使用 `pagenest` 文本域并在输出处转义。不要翻译持久设置键、API 路径、事件名称或内容锚点。

### 主题翻译

界面使用英文 gettext 原文与 `pagenest` 文本域，`after_setup_theme` 加载 `languages/`，现有站长文案与正文不经过 gettext。小型浏览器脚本通过 `wp_localize_script` 接收翻译，不新增运行依赖。新增 PHP 文案用字面量 `__`／转义变体；计数用 `_n`，可变内容使用完整句子和编号占位符。主题 JSON 名称、归档模板名及主题描述也进入提取清单。

`npm run i18n` 提取 POT 并按各 PO 头声明的语言与复数规则编译为确定性 MO；`npm run i18n:check` 自动发现 `languages/*.po`，检查覆盖率、全部复数形式、占位符和已提交产物，已接入 `npm test`。可用 `python3 tools/i18n.py --locale fr_FR` 编译／检查单个外部 locale；同一命令加 `--check` 只检查。PO 需完整声明匹配文件名的 Language 与有效 Plural-Forms，并提供全部 `msgstr[n]`。修改 PHP 后需重新提取引用行号；新增原文需在每个随包 PO 中补齐翻译。工具仅依赖 PHP 与 Python 标准库，不要求站点安装翻译插件。其他语言可使用标准 gettext 工具和 WordPress 语言包位置。

## 原生字体库与方向

`fonts.php` 只读取已发布的 `wp_font_family`／`wp_font_face`，`pagenest_font_body` 与 `pagenest_font_heading` 保存当前站点的 family ID，默认 `0`，不进入视觉 JSON。`pagenest_font_family($id)` 返回有效本地字形及展示名称；每个 face 必须拥有当前 `wp_get_font_dir()` 真实目录下的可读字体文件，URL 与网站同源，拒绝越界、缺失、远程来源和 CSS 描述符注入。支持 TTF／OTF／WOFF／WOFF2 并检查文件签名，最多读取每族 100 个 face，每个 face 最多 8 个来源。族名使用安全内部别名，字重、样式和 unicode-range 经白名单校验。

`pagenest_fonts_css()` 仅序列化当前选择的字形并去重，以 `font-display:swap` 输出且保留系统后备栈；不注入下载、预载入或后台 UI 字体。视觉变量经原有 appearance/editor 流程进入前台与正文画布；`--pagenest-body-font`、`--pagenest-reading-font`、`--pagenest-heading-font` 只在有效选择时输出。代码与数学沿用各自字体。缺少字体库能力、记录撤回或文件失效均回退，不迁移字体对象。

方向由原生语言属性决定；主题自己的布局采用 inline-start/end 逻辑属性，方向图标单独镜像。代码和数学仅在局部保留 `direction:ltr; unicode-bidi:isolate`，主题自有设置说明使用 `unicode-bidi:plaintext` 处理英文回退标点，不强制正文或第三方组件方向。随包 `zh_CN`／`zh_TW` 为主题 UI 翻译；外部 locale 可用 WordPress 原生 MO 加载流程，复数公式由各目录声明。主题不提供文章翻译关系、语言切换路由或翻译服务。

## 公开内容区与页眉页脚组合

`regions.php` 管理 `pagenest_header_layout`（`standard/two-row`）、`pagenest_footer_layout`（`standard/stacked`）以及 `pagenest_region_{after_header|before_footer}_{source|scope}`。布局默认 `standard` 并进入视觉 JSON；来源默认 `0`，scope 默认 `off`，仅接受 `off/all/front/blog/archive/posts/pages`，对象 ID 和显示条件不进入视觉 JSON。设置位于现有 PageNest 面板的 `pagenest_regions` 节，来源采用原生 `dropdown-pages` 和 `validate_callback`，不注册 CPT、REST 或正文写入入口。

`pagenest_region_source($id)` 校验来源页面、公开无密码的祖先链（最多 32 层并检测环），在解析前限制 65536 字节。解析结果递归限制 200 节点、16 层，只允许 `core/paragraph/heading/list/list-item/group/columns/column/buttons/button/image/separator/spacer`，当前支持时允许 `core/details`。非空自由 HTML、其他区块、任意层数据绑定、自定义 anchor／HTML id 属性一律返回 `WP_Error`。成功后将 Group／Buttons 的白名单布局属性转换为区域专用 `data-pagenest-*` 标记，再执行 `serialize_blocks` 与 `wp_kses_post`，不调用 `the_content`、`do_blocks`、`render_block`、短代码或渲染钩子。字面方括号文本保留。布局支持默认／constrained 分组、flex 横排／堆叠的原生对齐与 wrap／nowrap、默认自动网格及 1–12 个固定列；600px 以下网格改为单列，nowrap 横排在自身内横向滚动。自定义宽度、网格子项定位、响应式属性及未支持字段返回 `region_layout` 错误，不静默改变保存的布局。区域标题使用所选标题字体，无选择时继续继承正文。

`pagenest_region_content($slot)` 按公开请求及白名单页面类型决定输出，每次重新核对权限无关的公开状态；不展示当前来源自身，不按角色决定内容。`pagenest_render_region($slot)` 仅为非空结果输出容器，模板直接放在 header 之后和 footer 之前。管理员配置中心列出已启用内容区状态，编辑链接另核对 `edit_post`。没有内容缓存、TTL 或数据库迁移。

两行页眉沿用原 DOM 顺序与 `pagenest_header_actions` 钩子，仅桌面改为两行；内容区不进入 sticky。`reading.js` 使用 header 实际矩形与 ResizeObserver，文章阅读栏和无阅读栏的两行页眉页面均更新锚点偏移；无脚本的两行页眉不固定，避免长菜单遮挡正文锚点。区域的分栏、按钮、图片使用局部样式和已有语义 token，不依赖动态区块样式生成。

## Site presentation contract

`pagenest_color_scheme` accepts `light` (default), `dark`, or `system`. The HTML root exposes
`data-pagenest-color-scheme`; semantic `--pagenest-*` colors include `--pagenest-on-accent` for text on
accent backgrounds. CSS handles system media changes; `pagenest-color-scheme-change` also reports
`{mode,effective}` for optional consumers. The browser override uses `pagenest-site-appearance-v1`
and is independent of paragraph records and article reader preferences. No account data is stored. The single header button cycles light → dark → system; its accessible
name announces the current mode and next action. Without a saved override, the site default applies.
A click saves the selected mode without rewriting other existing browser preferences.

`pagenest_layout_article`, `pagenest_layout_topic`, and `pagenest_layout_tool` default to `standard`.
Per-object `_pagenest_layout` metadata can override the display context/preset through an authorized
nonce-protected save. Invalid combinations fall back to standard. Body classes identify the effective
context and preset; they confer no tool registration or content access. Companion remains usable with
other themes through its own shortcode UI and CSS fallbacks.

- `pagenest_series_directory($directory, $post_id)` filter：阅读侧栏的系列目录入口，默认空数组。扩展可返回 `url`、`title`；主题转义后展示。关系、权限及祖先验证由提供者负责，主题不读取 Companion 的元数据或配置。Companion 的章节顶部已有返回目录入口时不重复显示侧栏入口。
