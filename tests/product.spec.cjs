const { test, expect } = require('@playwright/test');
test('configuration hub exposes explicit forms and Customizer sections', async ({
  page,
}, testInfo) => {
  await page.goto('/product.html');
  await expect(page.locator('.pagenest-settings-header')).toContainText('PageNest');
  await expect(page.locator('a[href*="autofocus%5Bsection%5D"]')).toHaveCount(13);
  const forms = page.locator('form');
  await expect(forms).toHaveCount(2);
  for (const form of await forms.all()) {
    await expect(form).toHaveAttribute('method', 'post');
    await expect(form.locator('[name="_wpnonce"]')).toHaveCount(1);
  }
  await expect(page.locator('textarea')).toHaveAttribute('required', '');
  await expect(page.locator('textarea')).toHaveAttribute('maxlength', '16384');
});

for (const width of [1440, 390]) {
  test(`six editable pattern previews at ${width}px`, async ({ page }, testInfo) => {
    await page.setViewportSize({ width, height: 900 });
    await page.route('**/*', (route) =>
      new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort(),
    );
    await page.goto('/product.html');
    await page.locator('.pagenest-pattern-section > summary').click();
    await expect(page.locator('.pagenest-pattern-previews > figure')).toHaveCount(6);
    const faq = page.locator('.pagenest-pattern-previews > figure').last();
    await faq.locator('details').first().locator('summary').click();
    await expect(faq.locator('details').first()).toHaveAttribute('open', '');
    await expect(faq.locator('details').first().locator('p')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
    await faq.scrollIntoViewIfNeeded();
    await page.screenshot({
      path: testInfo.outputPath(`pattern-preview-${width}.png`),
      fullPage: true,
    });
  });
}
