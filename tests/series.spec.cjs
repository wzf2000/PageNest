const { test, expect } = require('@playwright/test');
const path = require('node:path');
for (const width of [1440, 390]) {
  test(`series directory reading entry at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 844 });
    await page.route('**/*', (route) =>
      new URL(route.request().url()).origin === 'http://127.0.0.1:18779'
        ? route.continue()
        : route.abort(),
    );
    await page.goto('/article-series.html');
    const directory = page.locator('[data-pagenest-series-directory]');
    await expect(directory).toHaveCount(1);
    await expect(directory.getByRole('link')).toHaveAttribute(
      'href',
      'https://example.test/contents/',
    );
    await directory.scrollIntoViewIfNeeded();
    await expect(directory).toBeVisible();
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
    ).toBeTruthy();
    await page.screenshot({
      path: path.join(__dirname, '../.runtime', `series-reading-${width}.png`),
    });
  });
}
