const { test, expect } = require('@playwright/test');

for (const width of [1440, 390]) {
  test(`appearance defaults and configured reader/editor agree at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/article-public.html');
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(247, 246, 242)');
    await expect(page.locator('.pagenest-article-body')).toHaveCSS('font-size', '17px');
    await expect(page.locator('.pagenest-article')).toHaveCSS('border-radius', '12px');
    await page.goto('/article-appearance.html');
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(245, 241, 233)');
    await expect(page.locator('.pagenest-article-body')).toHaveCSS('font-size', '22px');
    await expect(page.locator('.pagenest-article-body')).toHaveCSS('color', 'rgb(69, 60, 50)');
    await expect(page.locator('.pagenest-article')).toHaveCSS('border-radius', '18px');
    await expect(page.locator('.pagenest-main')).toHaveCSS('max-width', '1320px');
    await expect(page.locator('.pagenest-exercise-hint')).toHaveCSS('color', 'rgb(255, 255, 255)');
    await expect(page.locator('.pagenest-exercise-hint')).toHaveCSS(
      'background-color',
      'rgb(255, 255, 255)',
    );
    expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(
      false,
    );
    await page.goto('/editor-appearance.html');
    await expect(page.locator('.editor-styles-wrapper')).toHaveCSS('font-size', '22px');
    await expect(page.locator('.editor-styles-wrapper')).toHaveCSS('color', 'rgb(69, 60, 50)');
    await expect(page.locator('.editor-styles-wrapper')).toHaveCSS(
      'background-color',
      'rgb(255, 253, 248)',
    );
  });

  test(`search suggestions, keyboard, empty/error fallback and URL safety at ${width}px`, async ({
    page,
  }) => {
    await page.setViewportSize({ width, height: 900 });
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.route('**/wp-json/pagenest/v1/discovery?*', async (route) => {
      const query = new URL(route.request().url()).searchParams.get('q');
      if (query === 'failure') return route.fulfill({ status: 503, body: 'Unavailable' });
      return route.fulfill({
        json: {
          items:
            query === 'empty'
              ? []
              : [
                  {
                    title: '<img src=x onerror=alert(1)>',
                    url: '/safe-result',
                    kind_label: '文章',
                  },
                  {
                    title: 'External result',
                    url: 'https://example.test/private',
                    kind_label: '页面',
                  },
                  { title: 'Unsafe result', url: 'javascript:alert(1)', kind_label: '页面' },
                ],
        },
      });
    });
    await page.goto('/search-discovery.html');
    await page.locator('.pagenest-header-search > summary').click();
    await expect(page.locator('.pagenest-header-search input')).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(page.locator('.pagenest-header-search')).not.toHaveAttribute('open');
    await expect(page.locator('.pagenest-header-search > summary')).toBeFocused();
    const form = page.locator('.pagenest-search-page form');
    await form.locator('[name="s"]').fill('match');
    await expect(form.locator('.pagenest-search-suggestions li')).toHaveCount(1);
    await expect(form.locator('.pagenest-search-suggestions a')).toHaveText(
      '<img src=x onerror=alert(1)>',
    );
    await expect(form.locator('.pagenest-search-suggestions img')).toHaveCount(0);
    await form.locator('[name="s"]').fill('empty');
    await expect(form.locator('.pagenest-search-status')).toContainText('没有匹配建议');
    await expect(form.locator('.pagenest-search-suggestions')).toBeHidden();
    await form.locator('[name="s"]').fill('failure');
    await expect(form.locator('.pagenest-search-status')).toContainText('可按回车继续搜索');
    await expect(form.locator('button[type="submit"]')).toBeEnabled();
    await form.locator('[name="s"]').fill('x');
    await expect(form.locator('.pagenest-search-status')).toBeEmpty();
    await expect(form.locator('.pagenest-search-suggestions')).toBeHidden();
    expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(
      false,
    );
    expect(errors).toEqual([]);
  });
}

test('IME waits for composition and stale suggestions cannot replace current results', async ({
  page,
}) => {
  const requests = [];
  await page.route('**/wp-json/pagenest/v1/discovery?*', async (route) => {
    const query = new URL(route.request().url()).searchParams.get('q');
    requests.push(query);
    if (query === 'old') await new Promise((resolve) => setTimeout(resolve, 1000));
    await route
      .fulfill({ json: { items: [{ title: query, url: '/result', kind_label: '文章' }] } })
      .catch(() => {});
  });
  await page.goto('/search-discovery.html');
  const form = page.locator('.pagenest-search-page form');
  const input = form.locator('[name="s"]');
  await input.evaluate((node) => {
    node.value = '中文';
    node.dispatchEvent(new InputEvent('input', { bubbles: true, isComposing: true }));
  });
  await page.waitForTimeout(450);
  expect(requests).toEqual([]);
  await input.dispatchEvent('compositionend');
  await expect(form.locator('.pagenest-search-suggestions a')).toHaveText('中文');
  await input.fill('old');
  await expect.poll(() => requests.includes('old')).toBe(true);
  await input.fill('new');
  await expect(form.locator('.pagenest-search-suggestions a')).toHaveText('new');
  await page.waitForTimeout(1100);
  await expect(form.locator('.pagenest-search-suggestions a')).toHaveText('new');
});

test('suggestion timeout keeps full search available', async ({ page }) => {
  await page.route('**/wp-json/pagenest/v1/discovery?*', async (route) => {
    await new Promise((resolve) => setTimeout(resolve, 9500));
    await route.abort().catch(() => {});
  });
  await page.goto('/search-discovery.html');
  const form = page.locator('.pagenest-search-page form');
  await form.locator('[name="s"]').fill('timeout');
  await expect(form.locator('.pagenest-search-status')).toContainText('可按回车继续搜索', {
    timeout: 10000,
  });
  await expect(form.locator('button[type="submit"]')).toBeEnabled();
});

test('native GET search preserves query and kind without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('http://127.0.0.1:18779/search-series.html');
  await page.route('http://127.0.0.1:18779/?*', (route) =>
    route.fulfill({ body: '<p>Native search received</p>' }),
  );
  const form = page.locator('.pagenest-search-page form');
  await form.locator('[name="s"]').fill('中文 query');
  await form.locator('[name="pn_kind"]').selectOption('page');
  await form.locator('.pagenest-search-advanced > summary').click();
  await form.locator('[name="pn_sort"]').selectOption('modified');
  await form.locator('[name="pn_series"]').fill('阅读方法');
  await form.locator('button[type="submit"]').click();
  const url = new URL(page.url());
  expect(url.searchParams.get('s')).toBe('中文 query');
  expect(url.searchParams.get('pn_kind')).toBe('page');
  expect(url.searchParams.get('pn_sort')).toBe('modified');
  expect(url.searchParams.get('pn_series')).toBe('阅读方法');
  expect(url.searchParams.get('pn_category')).toBe('0');
  await context.close();
});

for (const width of [1440, 390, 320]) {
  test(`search fields align in full and popover forms at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    for (const series of [false, true]) {
      await page.goto(series ? '/search-series.html' : '/search-discovery.html');
      await page.evaluate(() => {
        document.documentElement.dataset.pagenestColorScheme = 'dark';
      });
      const form = page.locator('.pagenest-search-page form');
      const check = async (target) => {
        const advanced = target.locator('.pagenest-search-advanced');
        await expect(advanced).not.toHaveAttribute('open');
        await advanced.locator('summary').click();
        const sizes = await target.evaluate((form) => {
          const filters = form.querySelector('.pagenest-search-advanced .pagenest-search-filters');
          return {
            columns: getComputedStyle(filters).gridTemplateColumns.split(' ').length,
            expectedColumns: form.clientWidth > 420 ? 3 : 1,
            fields: [...filters.querySelectorAll('.pagenest-search-field')].map((field) => {
              const label = field.querySelector('label').getBoundingClientRect();
              const control = field.querySelector('input,select').getBoundingClientRect();
              return {
                labelBottom: label.bottom,
                top: control.top,
                height: control.height,
                left: control.left,
                right: control.right,
              };
            }),
          };
        });
        expect(sizes.columns).toBe(sizes.expectedColumns);
        for (const field of sizes.fields) {
          expect(field.top).toBeGreaterThanOrEqual(field.labelBottom);
          expect(field.height).toBe(44);
          expect(field.left).toBeGreaterThanOrEqual(0);
          expect(field.right).toBeLessThanOrEqual(width);
        }
      };
      await check(form);
      if (width !== 320 && series)
        await page.screenshot({
          path: `.runtime/ui-refinement-search-page-${width}.png`,
          fullPage: true,
        });
      await page.locator('.pagenest-header-search > summary').click();
      await check(page.locator('.pagenest-search-popover form'));
      if (width !== 320 && series)
        await page.screenshot({
          path: `.runtime/ui-refinement-search-popover-${width}.png`,
          fullPage: true,
        });
      expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(
        false,
      );
    }
  });
}

test('filter changes refresh suggestions with all selected fields', async ({ page }) => {
  let params;
  await page.route('**/wp-json/pagenest/v1/discovery?*', (route) => {
    params = Object.fromEntries(new URL(route.request().url()).searchParams);
    return route.fulfill({ json: { items: [] } });
  });
  await page.goto('/search-series.html');
  const form = page.locator('.pagenest-search-page form');
  await form.locator('[name=s]').fill('阅读');
  await form.locator('[name=pn_kind]').selectOption('post');
  await form.locator('.pagenest-search-advanced > summary').click();
  await form.locator('[name=pn_sort]').selectOption('modified');
  await form.locator('[name=pn_series]').fill('阅读方法');
  await form.locator('[name=pn_series]').dispatchEvent('change');
  await expect.poll(() => params?.series).toBe('阅读方法');
  expect(params).toMatchObject({ q: '阅读', kind: 'post', sort: 'modified', category: '0' });
});

test('mobile results keep search compact and expose active native filters', async ({ browser }) => {
  const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const page = await context.newPage();
  await page.goto('http://127.0.0.1:18779/search-results.html');
  const form = page.locator('.pagenest-search-page form');
  await expect(form.locator('.pagenest-search-advanced')).not.toHaveAttribute('open');
  await expect(page.locator('.pagenest-entry .pagenest-excerpt')).toContainText(
    'tensor shapes [B,T,D] and [a,b]',
  );
  expect((await page.locator('.pagenest-entry').first().boundingBox()).y).toBeLessThan(520);
  await page.goto('http://127.0.0.1:18779/search-results-filtered.html');
  await expect(form.locator('.pagenest-search-advanced')).toHaveAttribute('open');
  await expect(form.locator('[name=pn_sort]')).toHaveValue('modified');
  await expect(form.locator('[name=pn_sort]')).toBeVisible();
  await expect(form).toHaveAttribute('method', 'get');
  await context.close();
});
