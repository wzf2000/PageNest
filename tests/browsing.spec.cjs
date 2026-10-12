const { test, expect } = require('@playwright/test');
for (const width of [1440, 390]) {
  test(`composed home and list presets at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.route('**/*', (route) =>
      new URL(route.request().url()).origin === 'http://127.0.0.1:18779'
        ? route.continue()
        : route.abort(),
    );
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/home-composed.html');
    expect(
      await page
        .locator('[data-pagenest-home-module]')
        .evaluateAll((nodes) => nodes.map((n) => n.dataset.pagenestHomeModule)),
    ).toEqual(['featured', 'topics', 'updated', 'destinations', 'latest', 'authors']);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('.pagenest-topic-status')).toHaveText('持续更新');
    for (const name of ['home-composed', 'list-standard', 'list-compact', 'article-no-cover']) {
      await page.goto(`/${name}.html`);
      expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(
        false,
      );
      if (name.startsWith('list')) {
        await expect(page.locator('.pagenest-post-list .pagenest-entry').first()).toBeVisible();
        await expect(page.locator('.pagenest-reading-meta').first()).toContainText('分钟阅读');
      }
      if (name === 'article-no-cover') {
        await expect(page.locator('.pagenest-cover')).toHaveCount(0);
        await expect(page.locator('#legacy-heading')).toHaveCount(1);
      }
    }
    expect(errors).toEqual([]);
  });
}
test('home modules and curated links work without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('http://127.0.0.1:18779/home-composed.html');
  await expect(page.locator('[data-pagenest-home-module="destinations"] a')).toHaveAttribute(
    'href',
    /post-1006$/,
  );
  await expect(page.locator('[data-pagenest-home-module="featured"] .pagenest-entry')).toHaveCount(
    2,
  );
  await context.close();
});
