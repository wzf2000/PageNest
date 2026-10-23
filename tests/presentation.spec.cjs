const { test, expect } = require('@playwright/test');
for (const width of [1440, 390]) {
  test(`site color mode persists across templates and follows system at ${width}px`, async ({
    page,
  }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.setViewportSize({ width, height: 900 });
    await page.emulateMedia({ colorScheme: 'light' });
    await page.goto('/article-public.html');
    const controls = page.locator('[data-pagenest-site-preferences]');
    await expect(controls).toBeVisible();
    await controls.locator('summary').click();
    await controls.locator('[data-pagenest-color-mode][value="dark"]').check();
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(23, 30, 38)');
    await expect(page.locator('.pagenest-article')).toHaveCSS(
      'background-color',
      'rgb(32, 40, 51)',
    );
    await page.goto('/home-configured.html');
    await expect(page.locator('html')).toHaveAttribute('data-pagenest-color-scheme', 'dark');
    await page.screenshot({ path: `.runtime/site-dark-home-${width}.png`, fullPage: true });
    await controls.locator('summary').click();
    await controls.locator('[data-pagenest-color-mode][value="system"]').check();
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(247, 246, 242)');
    await page.emulateMedia({ colorScheme: 'dark' });
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(23, 30, 38)');
    await controls.locator('[data-pagenest-color-mode][value="default"]').check();
    await expect(page.locator('html')).toHaveAttribute('data-pagenest-color-scheme', 'light');
    expect(
      await page.evaluate(() => localStorage.getItem('pagenest-site-appearance-v1')),
    ).toBeNull();
    expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(
      false,
    );
    expect(errors).toEqual([]);
  });
  test(`layout presets retain navigation controls at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/article-public.html');
    await page.evaluate(() => {
      document.documentElement.dataset.pagenestColorScheme = 'dark';
      document.body.classList.add('pagenest-context-article', 'pagenest-layout-focused');
    });
    await expect(page.locator('.pagenest-reader-preferences')).toBeVisible();
    await expect(page.locator('.pagenest-toc')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(
      false,
    );
    await page.screenshot({ path: `.runtime/site-dark-reading-${width}.png`, fullPage: true });
    await page.goto('/page-archives.html');
    await page.evaluate(() => {
      document.body.classList.add('pagenest-context-tool', 'pagenest-layout-workspace');
    });
    expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(
      false,
    );
  });
}
test('denied storage still supports current-page appearance', async ({ page }) => {
  await page.addInitScript(() => {
    Storage.prototype.setItem = () => {
      throw Error('Unavailable');
    };
  });
  await page.goto('/article-public.html');
  const controls = page.locator('[data-pagenest-site-preferences]');
  await controls.locator('summary').click();
  await controls.locator('[data-pagenest-color-mode][value="dark"]').check();
  await expect(page.locator('html')).toHaveAttribute('data-pagenest-color-scheme', 'dark');
  await expect(controls.locator('[role=status]')).toHaveText('Applied to this page');
});

for (const width of [320, 390]) {
  test(`mobile header preserves its brand with reading extensions at ${width}px`, async ({
    page,
  }) => {
    await page.setViewportSize({ width, height: 844 });
    await page.goto('/article-public.html');
    await page.evaluate(() => {
      document.documentElement.dataset.pagenestColorScheme = 'dark';
      document.querySelector('.pagenest-brand > span').textContent = 'PageNest';
      const actions = document.querySelector('.pagenest-header-actions');
      actions.insertAdjacentHTML(
        'afterbegin',
        '<button type="button">我的阅读</button><button type="button">段落评论</button>',
      );
    });
    const bounds = await page.evaluate(() => {
      const brand = document.querySelector('.pagenest-brand').getBoundingClientRect();
      const menu = document.querySelector('.pagenest-menu-toggle').getBoundingClientRect();
      const actions = document.querySelector('.pagenest-header-actions').getBoundingClientRect();
      const label = document.querySelector('.pagenest-brand > span');
      return {
        brandWidth: brand.width,
        brandCenter: brand.y + brand.height / 2,
        menuCenter: menu.y + menu.height / 2,
        actionsTop: actions.y,
        brandBottom: brand.bottom,
        labelWidth: label.clientWidth,
        labelContent: label.scrollWidth,
        overflow: document.documentElement.scrollWidth > innerWidth,
      };
    });
    expect(bounds.brandWidth).toBeGreaterThan(100);
    expect(bounds.labelWidth).toBeGreaterThanOrEqual(bounds.labelContent);
    expect(Math.abs(bounds.brandCenter - bounds.menuCenter)).toBeLessThanOrEqual(1);
    expect(bounds.actionsTop).toBeGreaterThanOrEqual(bounds.brandBottom);
    expect(bounds.overflow).toBe(false);
    await page.locator('.pagenest-menu-toggle').click();
    await expect(page.locator('#pagenest-nav')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.locator('.pagenest-menu-toggle')).toHaveAttribute('aria-expanded', 'false');
    await page.screenshot({ path: `.runtime/header-extensions-dark-${width}.png` });
  });
}

test('native login notices and internal links retain dark contrast', async ({ page }) => {
  await page.goto('/article-public.html');
  await page.evaluate(() => {
    document.documentElement.dataset.pagenestColorScheme = 'dark';
    document.body.className = 'login pagenest-login';
    document.body.innerHTML =
      '<div id="login"><div class="message">Logged out. <a href="#">Return</a></div><div class="notice notice-info">Reset password. <a href="#">Help</a></div><div class="success">Success. <a href="#">Continue</a></div><div id="login_error" class="notice notice-error">Invalid login. <a href="#">Reset password</a></div></div>';
  });
  // Reproduce WordPress defaults after theme styles to verify the scoped selectors win.
  await page.addStyleTag({
    content:
      '.login .message,.login .notice,.login .success{background:#fff;color:inherit;border-left:4px solid #2271b1;padding:12px}.login #login_error{background:#fcf0f1;color:inherit;border-left-color:#d63638}',
  });
  for (const selector of ['.message', '.notice-info', '.success', '#login_error']) {
    await expect(page.locator(selector)).toHaveCSS('background-color', 'rgb(32, 40, 51)');
    await expect(page.locator(selector)).toHaveCSS('color', 'rgb(220, 227, 236)');
    await expect(page.locator(selector + ' a')).toHaveCSS('color', 'rgb(165, 205, 248)');
  }
  await page.screenshot({ path: '.runtime/native-login-notices-dark.png' });
});

for (const height of [390, 320]) {
  test(`appearance stays reachable after feedback in a short ${height}px viewport`, async ({
    page,
  }) => {
    await page.setViewportSize({ width: 844, height });
    await page.goto('/article-public.html');
    const controls = page.locator('[data-pagenest-site-preferences]');
    await controls.locator('summary').click();
    await controls.locator('[data-pagenest-color-mode][value="dark"]').check();
    const panel = controls.locator(':scope > div');
    const bounds = await panel.boundingBox();
    expect(bounds.y).toBeGreaterThanOrEqual(0);
    expect(bounds.y + bounds.height).toBeLessThanOrEqual(height - 15);
    await panel.evaluate((node) => {
      node.scrollTop = node.scrollHeight;
    });
    const status = await controls.getByRole('status').boundingBox();
    expect(status.y + status.height).toBeLessThanOrEqual(height - 15);
    await page.keyboard.press('Escape');
    await expect(controls).not.toHaveAttribute('open');
    await expect(controls.locator('summary')).toBeFocused();
  });
}
