const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');
const localFont = path.resolve(__dirname, '../.runtime/frontend/assets/fonts/fixture.ttf');
for (const width of [1440, 390]) {
  test(`native direction and mixed reading at ${width}px`, async ({ page }, testInfo) => {
    await page.setViewportSize({ width, height: 900 });
    await page.route('**/*', (route) =>
      new URL(route.request().url()).origin === 'http://127.0.0.1:18779'
        ? route.continue()
        : route.abort(),
    );
    await page.goto('/typography-rtl.html');
    await expect(page.locator('html')).toHaveAttribute('lang', 'ar');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.locator('.pagenest-article-body')).toHaveCSS('text-align', 'start');
    await expect(page.locator('.pagenest-article-body code')).toHaveCSS('direction', 'ltr');
    await expect(page.locator('.pagenest-article-body .katex')).toHaveCSS('direction', 'ltr');
    await expect(page.locator('.pagenest-article-body')).toContainText('const answer = 42;');
    await expect(page.locator('.pagenest-community p').first()).toHaveCSS(
      'unicode-bidi',
      'plaintext',
    );
    if (width === 1440) {
      const rail = await page.locator('.pagenest-reading-rail').boundingBox();
      const article = await page.locator('.pagenest-article-column').boundingBox();
      expect(rail.x).toBeLessThan(article.x);
    } else {
      await page.locator('.pagenest-menu-toggle').click();
      await expect(page.locator('#pagenest-nav')).toBeVisible();
      await page.keyboard.press('Escape');
      await expect(page.locator('.pagenest-menu-toggle')).toBeFocused();
    }
    await page.locator('.pagenest-header-search > summary').click();
    const box = await page.locator('.pagenest-search-popover').boundingBox();
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(width);
    await page.screenshot({ path: testInfo.outputPath('rtl-reading.png'), fullPage: true });
    await page.goto('/typography-rtl-regions.html');
    await expect(page.locator('.pagenest-region')).toHaveCount(2);
    await expect(page.locator('.pagenest-footer-menu a[href="/deep"]')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
    await page.screenshot({ path: testInfo.outputPath('rtl-regions.png'), fullPage: true });
    await page.goto('/typography-tw.html');
    await expect(page.locator('html')).toHaveAttribute('lang', 'zh-TW');
    await expect(page.locator('html')).not.toHaveAttribute('dir', 'rtl');
    await expect(page.locator('.pagenest-header-search > summary')).toHaveText('搜尋');
  });
  test(`installed local font loads at ${width}px`, async ({ page }, testInfo) => {
    test.skip(
      !fs.existsSync(localFont),
      'Host has no system test font; native integration verifies real local assets separately',
    );
    await page.setViewportSize({ width, height: 900 });
    let loaded = false;
    page.on('response', (response) => {
      if (response.url().includes('/assets/fonts/fixture.ttf') && response.status() === 200)
        loaded = true;
    });
    await page.route('**/*', (route) =>
      new URL(route.request().url()).origin === 'http://127.0.0.1:18779'
        ? route.continue()
        : route.abort(),
    );
    await page.goto('/typography-font.html');
    expect(
      await page.evaluate(async () => {
        await document.fonts.load('16px PageNestLibrary4001');
        return document.fonts.check('16px PageNestLibrary4001');
      }),
    ).toBe(true);
    expect(loaded).toBe(true);
    await expect(page.locator('.pagenest-article-body')).toHaveCSS(
      'font-family',
      /PageNestLibrary4001/,
    );
    await expect(page.locator('.pagenest-article-body h2').first()).toHaveCSS(
      'font-family',
      /PageNestLibrary4001/,
    );
    expect(
      await page
        .locator('.pagenest-article-body code')
        .evaluate((node) => getComputedStyle(node).fontFamily),
    ).not.toContain('PageNestLibrary4001');
    expect(
      await page.evaluate(() => document.querySelectorAll('link[rel="preload"][as="font"]').length),
    ).toBe(0);
    await page.screenshot({ path: testInfo.outputPath('local-font.png') });
  });
  test(`font request failure keeps system fallback at ${width}px`, async ({ page }) => {
    test.skip(!fs.existsSync(localFont), 'Host has no system test font');
    await page.setViewportSize({ width, height: 900 });
    await page.route('**/*', (route) =>
      new URL(route.request().url()).origin === 'http://127.0.0.1:18779' &&
      !route.request().url().includes('/assets/fonts/')
        ? route.continue()
        : route.abort(),
    );
    await page.goto('/typography-font.html');
    const metrics = await page.evaluate(async () => {
      await document.fonts.ready;
      const node = document.querySelector('.pagenest-article-body');
      const family = getComputedStyle(node).fontFamily;
      const fallback = family.replace(/"?PageNestLibrary4001"?,\s*/, '');
      const ctx = document.createElement('canvas').getContext('2d');
      ctx.font = `17px ${family}`;
      const actual = ctx.measureText('Readable system fallback 123').width;
      ctx.font = `17px ${fallback}`;
      return {
        // WebKit may report check() true even when the declared face failed to load.
        // Inspect the declared face and actual rendered fallback metrics instead.
        faces: [...document.fonts]
          .filter((face) => face.family.replaceAll('"', '') === 'PageNestLibrary4001')
          .map((face) => face.status),
        actual,
        fallback: ctx.measureText('Readable system fallback 123').width,
      };
    });
    expect(metrics.faces).toEqual(['error']);
    expect(metrics.actual).toBe(metrics.fallback);
    await expect(page.locator('.pagenest-article-body')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
  });
}
