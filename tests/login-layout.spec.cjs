const { test, expect } = require('@playwright/test');
const path = require('node:path');
const fs = require('node:fs');
const root = path.resolve(__dirname, '..');
const markup = (language = 'zh') =>
  `<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0;font:14px/1.6 system-ui}#login{margin:auto}form{padding:24px}label{display:block}input:not([type=checkbox]){box-sizing:border-box;width:100%;height:44px}p.submit{display:flex;justify-content:flex-end}.pagenest-social-providers{display:grid;gap:10px}.pagenest-social-provider{display:grid;min-height:46px;box-sizing:border-box;align-items:center;border:1px solid #ddd;text-decoration:none;line-height:1.5}.pagenest-social-icon{width:20px;height:20px}.pagenest-social-divider{margin:20px 0 14px}${fs.readFileSync(path.join(root, 'assets/login.css'), 'utf8')}</style><body class="login pagenest-login"><main id="login"><form id="loginform"><label>Username<input id="user_login"></label><label>Password<input id="user_pass" type="password"></label><div class="pagenest-social-login-options"><p class="pagenest-social-divider">Or use another account</p><div class="pagenest-social-providers">${['QQ', '百度', 'GitHub', 'Google'].map((name) => `<a class="pagenest-social-provider" href="#${name}"><svg class="pagenest-social-icon" aria-hidden="true"></svg><span>${language === 'en' ? `Use ${name} to sign in` : `使用 ${name} 登录`}</span></a>`).join('')}</div></div><div data-synthetic-captcha><button type="button">Validate</button></div><label><input id="rememberme" type="checkbox">Remember me</label><p class="submit"><button id="wp-submit" type="submit">Log in</button></p><input type="hidden" name="redirect_to" value="/account"></form></main>`;
for (const width of [320, 390]) {
  test(`login keeps credentials, validation and submit ahead of optional accounts at ${width}px`, async ({
    page,
  }) => {
    await page.setViewportSize({ width, height: 844 });
    await page.setContent(markup());
    await page.evaluate(
      () => (window.originalOptions = document.querySelector('.pagenest-social-login-options')),
    );
    await page.addScriptTag({ path: path.join(root, 'assets/login.js') });
    expect(
      await page.evaluate(
        () =>
          document
            .querySelector('#wp-submit')
            .compareDocumentPosition(document.querySelector('.pagenest-social-login-options')) &
          Node.DOCUMENT_POSITION_FOLLOWING,
      ),
    ).toBeTruthy();
    expect(
      await page.evaluate(
        () => window.originalOptions === document.querySelector('.pagenest-social-login-options'),
      ),
    ).toBe(true);
    await expect(page.locator('[data-synthetic-captcha]')).toHaveCount(1);
    await expect(page.locator('[name=redirect_to]')).toHaveValue('/account');
    await page.locator('#user_pass').focus();
    await page.keyboard.press('Tab');
    await expect(page.getByRole('button', { name: 'Validate' })).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.locator('#rememberme')).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.locator('#wp-submit')).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.locator('.pagenest-social-provider').first()).toBeFocused();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
  });
}
test('login layout preserves every option when JavaScript is unavailable', async ({ browser }) => {
  const context = await browser.newContext({
    javaScriptEnabled: false,
    viewport: { width: 320, height: 844 },
  });
  const page = await context.newPage();
  await page.setContent(markup());
  await expect(page.locator('#wp-submit')).toBeVisible();
  await expect(page.locator('.pagenest-social-provider')).toHaveCount(4);
  await expect(page.locator('[data-synthetic-captcha]')).toBeVisible();
  await context.close();
});

for (const language of ['zh', 'en']) {
  for (const width of [360, 390]) {
    test(`optional account labels fit and wrap as whole words in ${language} at ${width}px`, async ({
      page,
    }) => {
      await page.setViewportSize({ width, height: 844 });
      await page.setContent(markup(language));
      await page.addScriptTag({ path: path.join(root, 'assets/login.js') });
      for (const label of await page.locator('.pagenest-social-provider > span').all()) {
        const layout = await label.evaluate((node) => {
          const bounds = node.parentElement.getBoundingClientRect();
          const text = node.firstChild;
          const words = node.textContent.match(/\S+/g);
          let offset = 0;
          const boxes = words.map((word) => {
            const start = text.textContent.indexOf(word, offset);
            const range = document.createRange();
            range.setStart(text, start);
            range.setEnd(text, start + word.length);
            offset = start + word.length;
            return [...range.getClientRects()].map((rect) => ({
              left: rect.left,
              right: rect.right,
              top: rect.top,
            }));
          });
          return {
            left: bounds.left,
            right: bounds.right,
            boxes,
            height: node.getBoundingClientRect().height,
            lineHeight: parseFloat(getComputedStyle(node).lineHeight),
          };
        });
        expect(layout.height).toBeLessThanOrEqual(layout.lineHeight * 2 + 1);
        for (const word of layout.boxes) {
          expect(new Set(word.map((rect) => Math.round(rect.top))).size).toBe(1);
          for (const rect of word) {
            expect(rect.left).toBeGreaterThanOrEqual(layout.left);
            expect(rect.right).toBeLessThanOrEqual(layout.right);
          }
        }
      }
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
        true,
      );
    });
  }
}
