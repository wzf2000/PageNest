const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');

test('TinyMCE inline JavaScript restores CSS and cannot terminate its script', async ({ page }) => {
  const fixture = JSON.parse(
    execFileSync('php', ['tests/appearance.php', '--tinymce-fixture'], { encoding: 'utf8' }),
  );
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  // Match WordPress _parse_init: quote the already escaped TinyMCE string as-is.
  await page.setContent(
    `<script>window.styles={content_style:"${fixture.encoded}",theme:"${fixture.theme}"};</script>`,
  );
  expect(await page.evaluate(() => window.styles.content_style)).toBe(fixture.original);
  expect(await page.evaluate(() => window.styles.theme)).toBe(fixture.themeOriginal);
  expect(await page.evaluate(() => window.injected)).toBeUndefined();
  expect(errors).toEqual([]);
});
