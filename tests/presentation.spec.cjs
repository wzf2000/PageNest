const { test, expect } = require('@playwright/test');
test.beforeEach(async ({ page }) => {
  await page.route('**/*', (route) => {
    const host = new URL(route.request().url()).hostname;
    return ['127.0.0.1', 'localhost'].includes(host) ? route.continue() : route.abort();
  });
});
test('header keeps original links and native search usable without JavaScript', async ({
  browser,
}, testInfo) => {
  const context = await browser.newContext({
    javaScriptEnabled: false,
    viewport: { width: 1440, height: 900 },
  });
  const page = await context.newPage();
  await page.route('**/*', (route) =>
    new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort(),
  );
  await page.goto(new URL('/article-public.html', testInfo.project.use.baseURL).href);
  await expect(page.locator('[data-pagenest-cycle-mode]')).toBeHidden();
  await expect(page.locator('.pagenest-account-direct')).toBeVisible();
  const auxiliaryLabels = await page
    .locator('.pagenest-header .screen-reader-text')
    .evaluateAll((nodes) =>
      nodes.map((node) => ({
        width: node.getBoundingClientRect().width,
        height: node.getBoundingClientRect().height,
      })),
    );
  expect(auxiliaryLabels.length).toBeGreaterThan(0);
  for (const label of auxiliaryLabels) {
    expect(label.width).toBeLessThanOrEqual(1);
    expect(label.height).toBeLessThanOrEqual(1);
  }
  await page.locator('.pagenest-header-search > summary').click();
  await expect(page.locator('.pagenest-header-search input[type="search"]')).toBeVisible();
  await context.close();
});
for (const direction of ['ltr', 'rtl']) {
  test(`two-row header keeps visible and keyboard order aligned without Companion in ${direction}`, async ({
    page,
  }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/regions-article.html');
    await page.evaluate((direction) => (document.documentElement.dir = direction), direction);
    const header = page.locator('.pagenest-header-two-row');
    const nav = header.locator('#pagenest-nav');
    const search = header.locator('.pagenest-header-search > summary');
    const navigation = await nav.boundingBox(),
      utility = await search.boundingBox();
    if (direction === 'ltr')
      expect(navigation.x + navigation.width).toBeLessThanOrEqual(utility.x + 1);
    else expect(utility.x + utility.width).toBeLessThanOrEqual(navigation.x + 1);
    expect(
      Math.abs(navigation.y + navigation.height / 2 - (utility.y + utility.height / 2)),
    ).toBeLessThan(4);
    await expect(header.locator('.pagenest-account-menu')).toHaveCount(0);
    await nav.locator('.pagenest-account-direct').focus();
    await page.keyboard.press('Tab');
    await expect(search).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(header.locator('[data-pagenest-cycle-mode]')).toBeFocused();
  });
  test(`appearance cycle preserves three modes and system icon in ${direction}`, async ({
    page,
  }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/article-public.html');
    await page.evaluate((direction) => (document.documentElement.dir = direction), direction);
    const cycle = page.locator('[data-pagenest-cycle-mode]');
    for (const mode of ['dark', 'system', 'light']) {
      await cycle.click();
      await expect(cycle).toHaveAttribute('data-mode', mode);
      await expect(page.locator('html')).toHaveAttribute('data-pagenest-color-scheme', mode);
      expect(
        await page.evaluate(
          () => JSON.parse(localStorage.getItem('pagenest-site-appearance-v1')).scheme,
        ),
      ).toBe(mode);
      await expect(cycle).toHaveAttribute('aria-label', /Switch to|切换为|切換為/);
      if (mode === 'system') {
        await page.emulateMedia({ colorScheme: 'dark' });
        await expect(cycle.locator('[data-icon="monitor"]')).toBeVisible();
      }
    }
    await expect(page.locator('.pagenest-appearance-controls details')).toHaveCount(0);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
  });
}
for (const width of [1440, 390]) {
  test(`site color mode persists across templates and follows system at ${width}px`, async ({
    page,
  }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.setViewportSize({ width, height: 900 });
    await page.emulateMedia({ colorScheme: 'light' });
    await page.goto('/article-public.html');
    const controls = page.locator('.pagenest-appearance-controls');
    await expect(controls).toBeVisible();
    await controls.locator('[data-pagenest-cycle-mode]').click();
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(23, 30, 38)');
    await expect(page.locator('.pagenest-article')).toHaveCSS(
      'background-color',
      'rgb(32, 40, 51)',
    );
    await page.goto('/home-configured.html');
    await expect(page.locator('html')).toHaveAttribute('data-pagenest-color-scheme', 'dark');
    await page.screenshot({ path: `.runtime/site-dark-home-${width}.png`, fullPage: true });
    await controls.locator('[data-pagenest-cycle-mode]').click();
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(247, 246, 242)');
    await page.emulateMedia({ colorScheme: 'dark' });
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(23, 30, 38)');
    await controls.locator('[data-pagenest-cycle-mode]').click();
    await expect(page.locator('html')).toHaveAttribute('data-pagenest-color-scheme', 'light');
    expect(
      await page.evaluate(
        () => JSON.parse(localStorage.getItem('pagenest-site-appearance-v1')).scheme,
      ),
    ).toBe('light');
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
  const controls = page.locator('.pagenest-appearance-controls');
  await controls.locator('[data-pagenest-cycle-mode]').click();
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

test('unsaved mode follows site default and storage changes without migration', async ({
  page,
}) => {
  await page.route('**/assets/presentation-*.js', async (route) => {
    const response = await route.fetch();
    await route.fulfill({
      response,
      body: 'window.PageNestPresentation.defaultScheme="system";\n' + (await response.text()),
    });
  });
  await page.goto('/article-public.html');
  const cycle = page.locator('[data-pagenest-cycle-mode]');
  await expect(cycle).toHaveAttribute('data-mode', 'system');
  expect(await page.evaluate(() => localStorage.getItem('pagenest-site-appearance-v1'))).toBeNull();
  await cycle.click();
  await expect(cycle).toHaveAttribute('data-mode', 'light');
  await page.evaluate(() => {
    localStorage.setItem('pagenest-site-appearance-v1', JSON.stringify({ scheme: 'dark' }));
    dispatchEvent(new StorageEvent('storage', { key: 'pagenest-site-appearance-v1' }));
  });
  await expect(cycle).toHaveAttribute('data-mode', 'dark');
});
