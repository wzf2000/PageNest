const { test, expect } = require('@playwright/test');
for (const width of [1440, 390]) {
  for (const javaScriptEnabled of [true, false]) {
    test(`public static regions and layout at ${width}px JS=${javaScriptEnabled}`, async ({
      browser,
    }, testInfo) => {
      const context = await browser.newContext({
        viewport: { width, height: 900 },
        javaScriptEnabled,
      });
      const page = await context.newPage();
      await page.route('**/*', (route) =>
        new URL(route.request().url()).origin === 'http://127.0.0.1:18779'
          ? route.continue()
          : route.abort(),
      );
      await page.goto('/regions-list.html');
      await expect(page.locator('.pagenest-region')).toHaveCount(2);
      expect(
        await page
          .locator('.pagenest-region-after_header')
          .evaluate(
            (node) => node.previousElementSibling.matches('header') && !node.closest('header'),
          ),
      ).toBe(true);
      expect(
        await page
          .locator('.pagenest-region-before_footer')
          .evaluate((node) => node.nextElementSibling.matches('footer') && !node.closest('footer')),
      ).toBe(true);
      await expect(
        page.locator('.pagenest-region-after_header .wp-block-button__link').first(),
      ).toBeVisible();
      await page.locator('.pagenest-region-after_header summary').click();
      await expect(page.locator('.pagenest-region-after_header details')).toHaveAttribute(
        'open',
        '',
      );
      await expect(page.locator('.pagenest-region-after_header')).toContainText('[B,T,D]');
      await expect(page.locator('.pagenest-footer-menu a[href="/deep"]')).toBeVisible();
      await expect(page.locator('.pagenest-feed a')).toBeVisible();
      expect(
        Math.abs(
          (await page.locator('.pagenest-feed').boundingBox()).x -
            (await page.locator('.pagenest-footer').boundingBox()).x,
        ),
      ).toBeLessThan(2);
      const brand = await page.locator('.pagenest-brand').boundingBox();
      const nav = await page.locator('#pagenest-nav').boundingBox();
      if (width === 1440) expect(nav.y).toBeGreaterThanOrEqual(brand.y + brand.height);
      else if (javaScriptEnabled) {
        await page.locator('.pagenest-menu-toggle').click();
        await expect(page.locator('#pagenest-nav')).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.locator('.pagenest-menu-toggle')).toBeFocused();
      } else await expect(page.locator('#pagenest-nav')).toBeVisible();
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
        true,
      );
      if (javaScriptEnabled) {
        await page.goto('/regions-article.html');
        await expect(page.locator('.pagenest-region')).toHaveCount(2);
        const heading = page.locator('.pagenest-article-body h2[id]').first();
        await heading.evaluate((node) => node.scrollIntoView());
        await page.waitForTimeout(100);
        expect((await heading.boundingBox()).y).toBeGreaterThanOrEqual(
          (await page.locator('.pagenest-header').boundingBox()).height - 1,
        );
      }
      if (!javaScriptEnabled) {
        await page.screenshot({
          path: testInfo.outputPath(`regions-${width}.png`),
          fullPage: true,
        });
      }
      await context.close();
    });
  }
}
test('static regions retain dark palette', async ({ page }, testInfo) => {
  await page.route('**/*', (route) =>
    new URL(route.request().url()).origin === 'http://127.0.0.1:18779'
      ? route.continue()
      : route.abort(),
  );
  await page.goto('/regions-dark.html');
  await page.evaluate(() => {
    document.documentElement.dataset.pagenestColorScheme = 'dark';
  });
  await expect(
    page.locator('.pagenest-region-after_header .wp-block-button__link').first(),
  ).toBeVisible();
  await page.screenshot({
    path: testInfo.outputPath('regions-dark.png'),
    fullPage: true,
  });
});

for (const width of [1440, 390]) {
  test(`saved native region layout and distinct heading family at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/regions-heading-font.html');
    const region = page.locator('.pagenest-region-after_header');
    const row = region.locator('.fixture-row');
    await expect(row).toHaveCSS('display', 'flex');
    await expect(row).toHaveCSS('justify-content', 'center');
    const rowChildren = await row.locator(':scope > p').all();
    const a = await rowChildren[0].boundingBox();
    const b = await rowChildren[1].boundingBox();
    expect(Math.abs(a.y - b.y)).toBeLessThan(1);
    expect(b.x).toBeGreaterThan(a.x + a.width);
    const stack = region.locator('.fixture-stack');
    await expect(stack).toHaveCSS('flex-direction', 'column');
    await expect(stack).toHaveCSS('align-items', 'center');
    const stackA = await stack.locator('p').nth(0).boundingBox();
    const stackB = await stack.locator('p').nth(1).boundingBox();
    expect(stackB.y).toBeGreaterThan(stackA.y + stackA.height);
    const buttons = region.locator('.fixture-buttons');
    await expect(buttons).toHaveCSS('justify-content', 'center');
    const buttonContainer = await buttons.boundingBox();
    const firstButton = await buttons.locator('a').first().boundingBox();
    const lastButton = await buttons.locator('a').last().boundingBox();
    expect(
      Math.abs(
        (firstButton.x + lastButton.x + lastButton.width) / 2 -
          (buttonContainer.x + buttonContainer.width / 2),
      ),
    ).toBeLessThan(2);
    for (const selector of ['.fixture-grid', '.fixture-grid-auto']) {
      const grid = region.locator(selector);
      await expect(grid).toHaveCSS('display', 'grid');
      const cells = await grid.locator(':scope > p').all();
      const boxes = await Promise.all(cells.map((cell) => cell.boundingBox()));
      if (width === 1440) expect(Math.abs(boxes[0].y - boxes[2].y)).toBeLessThan(1);
      else expect(boxes[1].y).toBeGreaterThan(boxes[0].y + boxes[0].height);
    }
    await expect(region.locator('h2').first()).toHaveCSS('font-family', 'monospace');
    await expect(region.locator('.fixture-row p').first()).toHaveCSS('font-family', 'serif');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
    await page.goto('/regions-list.html');
    const inherited = await page
      .locator('.pagenest-region-after_header h2')
      .first()
      .evaluate((node) => ({
        heading: getComputedStyle(node).fontFamily,
        parent: getComputedStyle(node.parentElement).fontFamily,
      }));
    expect(inherited.heading).toBe(inherited.parent);
  });
}
