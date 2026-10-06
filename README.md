# PageNest · 栖页

A reading-focused WordPress theme for technical notes and thoughtful writing.

安装包与发行记录见 [GitHub Releases](https://github.com/wzf2000/PageNest/releases)。

让知识与思考安放于页间。PageNest 是独立经典 WordPress 主题，提供专题首页、文章阅读布局、作者展示、响应式导航与可配置登录外观。

## 功能

- 首页背景、六个专题、七位作者展示，可在原生自定义器配置。
- 桌面两列文章卡片与全宽 16:9 封面，手机单列布局。
- 文章目录根据 h1–h6 生成；没有小标题时提供 “正文” 入口。
- 文章、页面、归档、搜索、404、评论和原生密码保护。
- 登录/注册背景与提示语、页脚署名和可选备案号。

主题负责展示；评论邮件通知、旧 Markdown 编辑器和公式兼容由独立的 [PageNest Compatibility](https://github.com/wzf2000/PageNest-Compatibility) 提供。私人笔记、积分、社交登录与其他站点工具不在主题包内。

## 安装与升级

要求 WordPress 6.0+、PHP 8.0+。运行 `npm run package`，然后在 “外观 → 主题 → 安装主题” 上传 `dist/pagenest-0.5.1.zip`。打包需要 Git 工作副本与 Python 3；GitHub 下载的源码 ZIP 包含开发文件，正式安装优先使用打包命令生成的 ZIP。

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
npm test
npx playwright install --only-shell chromium
npm run test:frontend
npm run package
npm run package:check
```

PHP 使用 4 空格和 PER-CS 式括号规则，JS/CSS/JSON/YAML 使用 2 空格，目标 100 列。仓库中的 Markdown 文档由格式命令补齐中英文/数字间空格，同时保留代码块、行内代码和链接地址。

修改 `assets/` 中的原始 CSS/JS 后运行 `npm run build`；格式命令也会构建。生成文件名含内容哈希，不要手工修改生成副本。部署时先上传新资源，最后更新 `assets/manifest.php`；旧哈希文件可能仍被缓存页面引用。

## CI 与发行

两个仓库分别运行 push、pull request 和可复用的 CI。固定 Node.js 24.15.0、Python 3.12、Playwright 1.55.1；PHP 8.0 和 8.2 分别检查最低支持版本与当前运行版本。格式检查遵循 MarkBridge 的 Prettier、PHP 插件、Markdown 中英文间距和 Black 规则，Python 文件逐个检查以避免多进程启动。生成的哈希资源只校验，不直接格式化；CI 在构建前校验，避免构建掩盖已提交资源漂移。

PHP 测试使用内存中的 WordPress 替身，浏览器测试覆盖桌面与手机宽度，并禁止外部网络请求。它们验证公共功能和实际前端资源，不能代替完整 WordPress 安装上的插件组合验收。测试依赖、fixture、格式工具与 node_modules 不进入安装 ZIP。

手动运行 GitHub Actions 的 `Manual GitHub Release`，仅支持 main；输入无 `v` 的版本号，须与 PHP/主题头、package.json 和 package-lock.json 一致，并对应 CHANGELOG.md 的首个版本节。发行说明取自该节。默认 `publish=false`，生成可下载的安装 ZIP、SHA-256、外部 manifest 和发行说明。选择 `publish=true` 才创建公开发行：先固定源提交并完成同一套 CI，在独立写权限任务中复核下载附件摘要与源身份，创建带完整附件的草稿后公开。已存在的 tag 或 release 会被拒绝，避免覆盖既有 v0.5.1 或任何历史附件。工作流不会部署 WordPress。

打包使用固定 ZIP 时间戳与安装文件白名单，内外 manifest 保存源提交及逐文件摘要。`npm run package:check` 验证包结构、安装文件、checksum 与 proof；打包需要 Git 工作副本和 Python 3；安装文件必须与 HEAD 提交一致，先提交准备发行的修改再打包。

## License

Copyright (c) 2026 PageNest Contributors. Code and the included demo screenshot are licensed under **GPL-2.0-or-later**; see [LICENSE](LICENSE). The theme does not bundle site-uploaded artwork or media.
