const { test, expect } = require('@playwright/test');
for (const width of [1440, 390]) {
  test(`English navigation and reading controls at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/article-english.html');
    await expect(page.locator('html')).toHaveAttribute('lang', 'en-US');
    const controls = page.locator('[data-pagenest-reader-preferences]');
    await expect(controls).toBeVisible();
    await controls.locator('summary').click();
    await expect(controls.getByRole('group', { name: 'Font size' })).toBeVisible();
    await controls
      .getByRole('group', { name: 'Reading palette' })
      .getByRole('radio', { name: 'Dark', exact: true })
      .check();
    await expect(controls.getByRole('status')).toHaveText('Reading preferences saved.');
    await controls.getByRole('button', { name: 'Restore defaults' }).click();
    await expect(controls.getByRole('status')).toHaveText('Site defaults restored.');
    const toc = page.locator('.pagenest-toc');
    if ((await toc.getAttribute('open')) === null) await toc.locator('summary').click();
    await expect(toc.getByRole('button', { name: 'Expand all', exact: true })).toBeVisible();
    await toc.getByRole('button', { name: 'Collapse all', exact: true }).click();
    await expect(page.locator('.pagenest-table-scroll').first()).toHaveAttribute(
      'aria-label',
      'Horizontally scrollable table',
    );
    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth > innerWidth + 1,
    );
    expect(overflow).toBe(false);
    expect(errors).toEqual([]);
  });
}
test('English live search messages preserve native search fallback', async ({ page }) => {
  await page.route('**/wp-json/pagenest/v1/discovery**', (route) =>
    route.fulfill({
      json: {
        items: [
          {
            title: 'Synthetic discovery',
            url: 'http://127.0.0.1:18779/article-english.html',
            kind_label: 'Article',
          },
        ],
      },
    }),
  );
  await page.goto('/search-english.html');
  const form = page.locator('.pagenest-search-page form');
  await form.getByLabel('Search this site').fill('synthetic');
  await expect(form.getByRole('status')).toHaveText(
    'Suggestions: 1. Submit your search for all results.',
  );
  await expect(form.locator('a')).toHaveText('Synthetic discovery');
  await expect(form).toHaveAttribute('method', 'get');
});
test('English home and configuration labels come from the native domain', async ({ page }) => {
  await page.goto('/home-english.html');
  await expect(page.getByRole('link', { name: 'Browse articles', exact: false })).toBeVisible();
  await expect(
    page.getByRole('heading', { name: 'Start with a topic that interests you' }),
  ).toBeVisible();
  await page.goto('/product-english.html');
  await expect(
    page.locator('summary').filter({ hasText: 'Local read-only diagnostics' }),
  ).toBeVisible();
  await page.locator('summary').filter({ hasText: 'Transfer appearance settings' }).click();
  await expect(page.getByRole('button', { name: 'Import and save appearance' })).toBeVisible();
});
