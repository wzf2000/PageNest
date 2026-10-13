const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');
const previews = path.resolve(__dirname, '../.runtime/previews');
for (const width of [320, 390, 768, 1280, 1440]) {
  test(`editorial templates preserve responsive reading at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 1000 });
    await page.route('**/*', (route) =>
      new URL(route.request().url()).origin === 'http://127.0.0.1:18779'
        ? route.continue()
        : route.abort(),
    );
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    for (const fixture of ['editorial-home', 'editorial-article', 'editorial-long-title']) {
      await page.goto(`/${fixture}.html`);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
        true,
      );
      await expect(page.locator('h1').first()).toBeVisible();
      await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(247, 246, 242)');
      if (fixture === 'editorial-home') {
        const hero = await page.locator('.pagenest-hero').boundingBox();
        expect(hero.height).toBeLessThan(width < 768 ? 430 : 360);
        if (width === 1440 || width === 390) {
          fs.mkdirSync(previews, { recursive: true });
          await page.screenshot({ path: path.join(previews, `home-${width}.png`), fullPage: true });
        }
      }
      if (fixture === 'editorial-article') {
        await expect(page.locator('#legacy-heading')).toHaveCount(1);
        if (width === 1440 || width === 390)
          await page.screenshot({
            path: path.join(previews, `article-${width}.png`),
            fullPage: true,
          });
        const body = page.locator('.pagenest-article-body');
        const before = await body.boundingBox();
        await page.locator('.pagenest-reading-panel').evaluate((el) => {
          el.hidden = false;
          el.innerHTML = '<p>合成评论面板：这里保留正文宽度。</p>';
          el.closest('.pagenest-reading-rail').classList.add('pagenest-panel-active');
        });
        expect((await body.boundingBox()).width).toBe(before.width);
      }
    }
    expect(errors).toEqual([]);
  });
}
for (const width of [320, 768, 1440]) {
  test(`200 percent text remains readable at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 1000 });
    for (const fixture of ['editorial-home', 'editorial-long-title']) {
      await page.goto(`/${fixture}.html`);
      await page.addStyleTag({
        content:
          ':root { --pagenest-font-scale: 2; --pagenest-reading-size: 34px; font-size: 32px; }',
      });
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
        true,
      );
      await expect(page.locator('h1').first()).toBeVisible();
    }
  });
}
test('native settings hub groups tasks and keeps advanced actions explicit', async ({ page }) => {
  for (const width of [390, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto('/product.html');
    await expect(page.locator('.pagenest-settings-group')).toHaveCount(3);
    await expect(page.locator('.pagenest-pattern-previews figure')).toHaveCount(3);
    const details = page.locator('.pagenest-settings-details');
    await expect(details).toHaveCount(2);
    for (const item of await details.all()) await expect(item).not.toHaveAttribute('open');
    await expect(page.locator('#pagenest-json')).toBeHidden();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
    if (width === 1440)
      await page.screenshot({
        path: path.join(previews, 'configuration-1440.png'),
        fullPage: true,
      });
    await details.first().locator('summary').click();
    await expect(page.locator('#pagenest-json')).toBeVisible();
    const imported = page
      .locator('form')
      .filter({ has: page.locator('[value="pagenest_appearance_import"]') });
    await expect(imported).toHaveAttribute('method', 'post');
    await expect(imported.locator('button')).toBeEnabled();
    await details.nth(1).locator('summary').click();
    await expect(page.locator('.widefat')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
  }
});
test('reduced motion disables smooth reading scroll', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/editorial-article.html');
  await expect(page.locator('html')).toHaveCSS('scroll-behavior', 'auto');
});

test('uploaded hero artwork is visible and fits desktop and mobile', async ({ page }) => {
  for (const width of [320, 390, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto('/editorial-home-image.html');
    const art = page.locator('.pagenest-hero-art');
    await expect(art).toBeVisible();
    await expect(art).toHaveCSS('background-image', /fixture-cover/);
    const artwork = await art.boundingBox();
    const hero = await page.locator('.pagenest-hero').boundingBox();
    expect(artwork.x).toBeGreaterThanOrEqual(hero.x);
    expect(artwork.x + artwork.width).toBeLessThanOrEqual(hero.x + hero.width + 1);
    expect(artwork.y + artwork.height).toBeLessThanOrEqual(hero.y + hero.height + 1);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
  }
});
test('editorial article actions honor the local dark palette', async ({ page }) => {
  await page.goto('/editorial-article.html');
  await page
    .locator('.pagenest-article-footer')
    .evaluate((node) =>
      node.insertAdjacentHTML(
        'beforeend',
        '<button type="button" class="pagenest-like" data-pagenest-like="1001">点赞 <span>12</span></button>',
      ),
    );
  const controls = page.locator('[data-pagenest-reader-preferences]');
  await controls.locator('summary').click();
  await controls.locator('[data-reader-preference="palette"]').selectOption('dark');
  await expect(page.locator('.pagenest-article')).toHaveCSS('background-color', 'rgb(32, 40, 51)');
  await expect(page.locator('.pagenest-article-body')).toHaveCSS(
    'background-color',
    'rgb(32, 40, 51)',
  );
  const like = page.locator('.pagenest-like');
  await expect(like).toHaveCSS('color', 'rgb(165, 205, 248)');
  await expect(like).toHaveCSS('min-height', '44px');
  await like.evaluate((node) => node.setAttribute('aria-pressed', 'true'));
  await expect(like).toHaveCSS('background-color', 'rgb(44, 55, 69)');
  await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(247, 246, 242)');
});

for (const width of [320, 390, 1440]) {
  test(`saved base font settings scale all editorial titles at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 1000 });
    for (const template of ['home', 'article', 'list']) {
      const sizes = [];
      for (const baseSize of [14, 20]) {
        await page.goto(`/editorial-${template}-font-${baseSize}.html`);
        const metrics = await page
          .locator('h1')
          .first()
          .evaluate((title) => ({
            title: parseFloat(getComputedStyle(title).fontSize),
            scale: parseFloat(
              getComputedStyle(document.documentElement).getPropertyValue('--pagenest-font-scale'),
            ),
            root: getComputedStyle(document.documentElement).fontSize,
          }));
        expect(metrics.scale).toBe(baseSize / 16);
        expect(metrics.root).toBe('16px');
        sizes.push(metrics.title);
        await expect(page.locator('h1').first()).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
          true,
        );
      }
      expect(sizes[1] / sizes[0]).toBeCloseTo(20 / 14, 4);
    }
  });
}
