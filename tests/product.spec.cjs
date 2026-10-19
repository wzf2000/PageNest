const { test, expect } = require('@playwright/test');
test('configuration hub exposes explicit forms and Customizer sections', async ({ page }) => {
  await page.goto('/product.html');
  await expect(page.locator('.pagenest-settings-header')).toContainText('PageNest');
  await expect(page.locator('a[href*="autofocus%5Bsection%5D"]')).toHaveCount(12);
  const forms = page.locator('form');
  await expect(forms).toHaveCount(2);
  for (const form of await forms.all()) {
    await expect(form).toHaveAttribute('method', 'post');
    await expect(form.locator('[name="_wpnonce"]')).toHaveCount(1);
  }
  await expect(page.locator('textarea')).toHaveAttribute('required', '');
  await expect(page.locator('textarea')).toHaveAttribute('maxlength', '16384');
});
