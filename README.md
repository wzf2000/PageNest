# PageNest · 栖页

A reading-focused WordPress theme for technical notes and thoughtful writing.

让知识与思考安放于页间。PageNest 是独立经典 WordPress 主题，提供专题首页、文章阅读布局、作者展示、响应式导航与可配置登录外观。

## 功能

- 首页背景、六个专题、七位作者展示，可在原生自定义器配置。
- 桌面两列文章卡片与全宽16:9封面，手机单列布局。
- 文章目录根据h1–h6生成；没有小标题时提供“正文”入口。
- 文章、页面、归档、搜索、404、评论和原生密码保护。
- 登录/注册背景与提示语、页脚署名和可选备案号。

主题负责展示；评论邮件通知、旧Markdown编辑器/公式兼容由独立的[PageNest Compatibility](https://github.com/wzf2000/PageNest-Compatibility)提供。私人笔记、积分、社交登录与其他站点工具不在主题包内。主题中的兼容接口不代表内置这些服务。

## 安装

要求WordPress6.0+、PHP8.0+。实际站点验收环境为WordPress7.1/PHP8.2，以Chrome桌面/手机体验为主；不声称已验证全部版本/浏览器组合。

运行 `npm run package`，在外观→主题→安装主题中上传 `dist/wzf-journal-0.4.0.zip`。打包只需要Python3，不必先安装Node依赖；也可将运行文件放入 `wp-content/themes/wzf-journal/`。

**已有安装升级必须保留目录名 `wzf-journal`**，这样WordPress继续读取原有菜单、背景和自定义配置。本次公开不迁移内部前缀/配置键。GitHub下载的源代码ZIP包含开发文件，正式安装优先使用打包命令生成的ZIP。

启用后分配“全站主导航”，在自定义→栖页首页设置配置背景、专题和作者；站点身份设置标题与Logo。登录与注册、页脚信息是另外两个设置区。未配置专题/作者不会自动导入示例文章。图片由站点自行上传并确认使用权。

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

PHP使用4空格和PER-CS式括号规则，JS/CSS/JSON使用2空格，目标100列。Prettier与PHP插件处理代码，js-beautify整理混合HTML，Black处理Python。版本由package-lock.json和requirements-dev.txt记录。

修改assets中的原始CSS/JS后执行 `npm run build`；格式命令也会构建。生成文件名含内容哈希，不要手工修改生成副本。旧哈希文件在服务器上可能仍被缓存页面引用，部署时先上传新资源、最后更新manifest，不要立即删除旧资源。

公开仓库不含生产数据库、运维记录、账号、私密文章或历史站点截图。`screenshot.png`是本主题样式生成的演示页面截图，使用CSS渐变，无第三方插画。

## License

Copyright (c) 2026 wzf2000. Code and the included demo screenshot are licensed under **GPL-2.0-or-later**; see [LICENSE](LICENSE). The theme does not bundle site-uploaded artwork or media.
