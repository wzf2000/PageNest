const { test, expect } = require('@playwright/test');
for (const width of [1440, 390]) {
  test(`native listings and footer without JavaScript at ${width}px`, async ({
    browser,
  }, testInfo) => {
    const context = await browser.newContext({
      javaScriptEnabled: false,
      viewport: { width, height: 900 },
    });
    const page = await context.newPage();
    await page.route('**/*', (route) =>
      new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort(),
    );
    for (const listing of ['blog', 'archive', 'search']) {
      for (const layout of ['inherit', 'compact', 'grid-2', 'grid-3']) {
        for (const sidebar of ['show', 'hide']) {
          await page.goto(`/publishing-${listing}-${layout}-${sidebar}.html`);
          const cards = page.locator('.pagenest-post-list > article');
          await expect(cards).toHaveCount(6);
          await expect(page.locator('.pagenest-sidebar')).toHaveCount(sidebar === 'show' ? 1 : 0);
          await expect(page.locator('.pagenest-footer-menu a')).toHaveCount(4);
          await expect(page.locator('.pagenest-footer-menu a[href="/deep"]')).toBeVisible();
          await expect(page.locator('.pagenest-feed a')).toBeVisible();
          await expect(page.locator('.pagenest-pagination a')).toHaveAttribute('href', /paged=2/);
          await expect(cards.first().locator('.pagenest-excerpt')).toBeVisible();
          await expect(cards.first().locator('h2 a')).toHaveAttribute('href', /1001/);
          expect(
            await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
          ).toBe(true);
          if (layout.startsWith('grid')) {
            const columns = await page
              .locator('.pagenest-post-list')
              .evaluate((node) => getComputedStyle(node).gridTemplateColumns.split(' ').length);
            expect(columns).toBe(
              width === 390 ? 1 : layout === 'grid-3' && sidebar === 'hide' ? 3 : 2,
            );
            expect((await cards.first().boundingBox()).width).toBeGreaterThan(
              width === 390 ? 250 : 290,
            );
          }
        }
      }
    }
    await page.goto('/publishing-blog-grid-3-hide.html');
    const title = page.locator('.pagenest-post-list h2 a').first();
    await title.focus();
    await expect(title).toBeFocused();
    expect(await title.evaluate((node) => getComputedStyle(node).outlineStyle)).not.toBe('none');
    await page.screenshot({
      path: testInfo.outputPath(`publishing-grid-${width}.png`),
      fullPage: true,
    });
    await page.locator('.pagenest-footer-menu').scrollIntoViewIfNeeded();
    await page.screenshot({ path: testInfo.outputPath(`publishing-footer-${width}.png`) });
    await context.close();
  });
}
test('dark grid retains links and focus', async ({ page }, testInfo) => {
  await page.route('**/*', (route) =>
    new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort(),
  );
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/publishing-archive-grid-3-show.html');
  await page.evaluate(() => {
    document.documentElement.dataset.pagenestColorScheme = 'dark';
  });
  const link = page.locator('.pagenest-post-list h2 a').first();
  await link.focus();
  await expect(link).toBeFocused();
  const colors = await link.evaluate((node) => ({
    link: getComputedStyle(node).color,
    background: getComputedStyle(document.body).backgroundColor,
  }));
  expect(colors.link).not.toBe(colors.background);
  await page.screenshot({ path: testInfo.outputPath('publishing-grid-dark.png'), fullPage: true });
});
