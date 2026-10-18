const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');

// Opt in with a real MarkBridge plugin source root; no private checkout path is required.
const pluginRoot = process.env.MARKBRIDGE_TEST_PLUGIN_ROOT;
for (const width of [1440, 390]) {
  test(`late theme CSS preserves MarkBridge code geometry at ${width}px`, async ({ page }) => {
    test.skip(!pluginRoot, 'Set MARKBRIDGE_TEST_PLUGIN_ROOT for cross-package CSS integration');
    await page.setViewportSize({ width, height: 900 });
    await page.setContent(`<!doctype html><main class="pagenest-article-body">
      <div class="wp-block-mbb-code-compare"><div class="mbb-teaching-body">
      <div class="mbb-compare-left"><pre class="mbb-code-display language-js"><code><span class="mbb-compare-line"><span class="mbb-compare-line-number">1</span> const answer = 42;</span></code></pre></div>
      <div class="mbb-compare-right"><pre class="mbb-code-display language-js"><code><span class="mbb-compare-line"><span class="mbb-compare-line-number">1</span> const answer = 43;</span></code></pre></div>
      </div></div>
      <div class="mbb-code-tab"><pre id="numbered" class="mbb-code-display line-numbers language-js"><code>const answer = 42;\nconsole.log(answer);<span class="line-numbers-rows" aria-hidden="true"><span></span><span></span></span></code></pre></div>
      <pre id="ordinary"><code>Ordinary code keeps theme spacing.</code></pre>
      </main>`);
    for (const file of [
      'vendor/prism/plugins/line-numbers/prism-line-numbers.css',
      'teaching.css',
      'code-display.css',
    ]) {
      await page.addStyleTag({ content: fs.readFileSync(path.join(pluginRoot, file), 'utf8') });
    }
    // WordPress can append the theme's content sheet after plugin resources.
    await page.addStyleTag({ path: path.join(__dirname, '../assets/content.css') });
    for (const pre of await page.locator('.wp-block-mbb-code-compare pre').all()) {
      await expect(pre).toHaveCSS('padding-inline-start', '0px');
      await expect(pre).toHaveCSS('padding-inline-end', '0px');
    }
    await expect(page.locator('#ordinary')).toHaveCSS('padding-left', '20px');
    const gutter = await page.locator('#numbered').evaluate((pre) => {
      const bounds = pre.getBoundingClientRect();
      const rows = pre.querySelector('.line-numbers-rows').getBoundingClientRect();
      const code = pre.querySelector('code').getBoundingClientRect();
      return { left: rows.left - bounds.left, right: code.left - rows.right, width: rows.width };
    });
    expect(gutter.left).toBeGreaterThanOrEqual(0);
    expect(gutter.right).toBeGreaterThanOrEqual(0);
    expect(gutter.width).toBeGreaterThan(30);
    await page.screenshot({ path: `.runtime/markbridge-code-cascade-${width}.png` });
  });
}
