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
    await controls.locator('select').selectOption('dark');
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(23, 30, 38)');
    await expect(page.locator('.pagenest-article')).toHaveCSS(
      'background-color',
      'rgb(32, 40, 51)',
    );
    await page.goto('/home-configured.html');
    await expect(page.locator('html')).toHaveAttribute('data-pagenest-color-scheme', 'dark');
    await page.screenshot({ path: `.runtime/site-dark-home-${width}.png`, fullPage: true });
    await controls.locator('summary').click();
    await controls.locator('select').selectOption('system');
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(247, 246, 242)');
    await page.emulateMedia({ colorScheme: 'dark' });
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(23, 30, 38)');
    await controls.locator('select').selectOption('default');
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
  await controls.locator('select').selectOption('dark');
  await expect(page.locator('html')).toHaveAttribute('data-pagenest-color-scheme', 'dark');
  await expect(controls.locator('[role=status]')).toHaveText('Applied to this page');
});
