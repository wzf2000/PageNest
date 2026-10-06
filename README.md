# PageNest · 栖页

A reading-focused WordPress theme for technical notes and thoughtful writing.

当前工作副本为 0.5.1，尚未公开发行。

让知识与思考安放于页间。PageNest 是独立经典 WordPress 主题，提供专题首页、文章阅读布局、作者展示、响应式导航与可配置登录外观。

## 功能

- 首页背景、六个专题、七位作者展示，可在原生自定义器配置。
- 桌面两列文章卡片与全宽 16:9 封面，手机单列布局。
- 文章目录根据 h1–h6 生成；没有小标题时提供 “正文” 入口。
- 文章、页面、归档、搜索、404、评论和原生密码保护。
- 登录/注册背景与提示语、页脚署名和可选备案号。

主题负责展示；评论邮件通知、旧 Markdown 编辑器和公式兼容由独立的 [PageNest Compatibility](https://github.com/wzf2000/PageNest-Compatibility) 提供。私人笔记、积分、社交登录与其他站点工具不在主题包内。

## 安装与升级

要求 WordPress 6.0+、PHP 8.0+。运行 `npm run package`，然后在 “外观 → 主题 → 安装主题” 上传 `dist/pagenest-0.5.1.zip`。打包只需要 Python 3；GitHub 下载的源码 ZIP 包含开发文件，正式安装优先使用打包命令生成的 ZIP。

从 0.4 升级到 0.5 时先备份，再安装并切换至 `pagenest` 目录。首次切换会通过 `legacy-migration.php` 的集中映射复制旧主题菜单、背景和自定义设置；原设置继续保留作回退依据。0.5 之后保持目录名 `pagenest`。

启用后分配 “全站主导航”，在 “自定义 → 栖页首页设置” 配置背景、专题和作者；站点标题与 Logo 使用 WordPress 的 “站点身份”。未配置专题或作者时不会自动导入示例文章。图片由站点自行上传并确认使用权。

## 展示设置与扩展接口

在 “自定义 → 栖页首页设置 → 首页介绍” 配置首页眉题、首页介绍和作者介绍；介绍支持换行。
“页脚信息”“交流区”“归档与关于链接” 分别配置页脚标语、交流说明与链接、导航链接。
留空隐藏对应文案；链接名称和地址均需非空。归档地址默认指向文章列表，可创建页面并选择
“文章归档” 模板后填写该页面地址。旧 `pages/page-archives.php` 模板元数据继续兼容。

新增设置（既有设置键保留）：

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

主题默认显示同分类的公开相关文章。扩展使用以下 WordPress 钩子接入自己的系列导航或文章操作：

- `pagenest_related_posts($posts, $post_id)` filter：返回文章对象或 ID 数组；主题在过滤后排除当前文章、重复、非文章、密码保护及无权读取的记录，最多显示三篇。仅公开文章和有权限的私密文章可显示。
- `pagenest_related_title($title, $post_id, $posts)` filter：设置相关文章标题，输出前统一转义。
- `pagenest_article_actions($post_id)` action：在无密码文章的页脚输出扩展按钮；扩展自行负责鉴权、脚本、状态与输出转义。

扩展顶栏按钮添加 `data-pagenest-header-action` 属性，主题初始化或收到 document 上的
`pagenest-integration-ready` 事件后，按 DOM 顺序将它们放在顶栏菜单按钮前。
属性值由扩展自行定义，事件无需 detail，可反复触发。
现有 `.pagenest-notes-slot` 和 `pagenest-reading-layout` 事件供阅读扩展复用；主题不读取笔记数据。
旧站点的锚点与 `.wzf-reading` 样式保留于兼容层，供已有正文与永久链接继续使用。

## 开发

```sh
npm ci --ignore-scripts
python3 -m venv .venv
. .venv/bin/activate
python3 -m pip install -r requirements-dev.txt
npm run format
npm run format:check
npm run package
```

PHP 使用 4 空格和 PER-CS 式括号规则，JS/CSS/JSON 使用 2 空格，目标 100 列。仓库中的 Markdown 文档由格式命令补齐中英文/数字间空格，同时保留代码块、行内代码和链接地址。

修改 `assets/` 中的原始 CSS/JS 后运行 `npm run build`；格式命令也会构建。生成文件名含内容哈希，不要手工修改生成副本。部署时先上传新资源，最后更新 `assets/manifest.php`；旧哈希文件可能仍被缓存页面引用。

## License

Copyright (c) 2026 PageNest Contributors. Code and the included demo screenshot are licensed under **GPL-2.0-or-later**; see [LICENSE](LICENSE). The theme does not bundle site-uploaded artwork or media.
