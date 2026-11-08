const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const path = require('node:path');
const assets = Object.fromEntries(
  [
    ...fs
      .readFileSync(path.join(__dirname, '../assets/manifest.php'), 'utf8')
      .matchAll(/'([^']+)' => '([^']+)'/g),
  ].map((match) => [match[1], match[2]]),
);
test.beforeEach(async ({ page }) => {
  await page.route('**/*', (route) =>
    ['127.0.0.1', 'localhost'].includes(new URL(route.request().url()).hostname)
      ? route.continue()
      : route.abort(),
  );
});

async function prepare(page, delayed = false) {
  await page.route('**/article-public.html', async (route) => {
    const response = await route.fetch();
    let html = await response.text();
    html = html.replaceAll('<h3>观察与记录', '<h3>观察与记录：长篇阅读中的问题与推理过程');
    html = html.replaceAll(
      '</h3>',
      '</h3><p class="reading-position-sample">' +
        '阅读时可以调整字号，保留段落位置与自然的浏览节奏。'.repeat(25) +
        '</p>',
    );
    html = html.replace(
      '</head>',
      ['header_reading_css', 'reading_tools_css']
        .map((key) => `<link rel="stylesheet" href="/assets/${assets[key]}">`)
        .join('') + '</head>',
    );
    html = html.replace(
      '</body>',
      ['overlays_js', 'header_reading_js', 'reading_tools_js']
        .map((key) => `<script src="/assets/${assets[key]}"></script>`)
        .join('') +
        (delayed ? '<img src="/reading-delayed.svg" width="1" height="1" alt="">' : '') +
        '</body>',
    );
    await route.fulfill({ response, body: html });
  });
}

test('long desktop directory follows the article without taking over manual scrolling', async ({
  page,
}) => {
  await page.setViewportSize({ width: 1440, height: 740 });
  await prepare(page);
  await page.goto('/article-public.html');
  await page.getByRole('button', { name: '展开全部', exact: true }).click();
  await page.evaluate(() => document.activeElement.blur());
  await page.mouse.move(400, 400);
  await page
    .locator('.pagenest-article-body h3')
    .last()
    .evaluate((node) => node.scrollIntoView({ behavior: 'instant', block: 'start' }));
  await expect
    .poll(() =>
      page.evaluate(() => {
        const rail = document.querySelector('.pagenest-reading-rail').getBoundingClientRect();
        const link = document
          .querySelector('.pagenest-toc a[aria-current]')
          .getBoundingClientRect();
        return link.top >= rail.top && link.bottom <= rail.bottom;
      }),
    )
    .toBe(true);
  expect(
    await page.evaluate(async () => {
      const rail = document.querySelector('.pagenest-reading-rail');
      rail.scrollTop = 0;
      dispatchEvent(new Event('scroll'));
      await new Promise(requestAnimationFrame);
      return rail.scrollTop;
    }),
  ).toBe(0);
});

test('nested reading settings retain the visible paragraph after a font change', async ({
  page,
}) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await prepare(page);
  await page.goto('/article-public.html');
  const sample = page.locator('.reading-position-sample').nth(4);
  await sample.evaluate((node) =>
    scrollTo({
      top: scrollY + node.getBoundingClientRect().top - 104,
      behavior: 'instant',
    }),
  );
  const top = await sample.evaluate((node) => node.getBoundingClientRect().top);
  await page
    .locator('.pagenest-reading-tools button')
    .last()
    .evaluate((node) => node.click());
  await page.getByRole('button', { name: 'Reading settings', exact: true }).click();
  await page.locator('[data-reader-preference="size"][value="22"]').check();
  await page.keyboard.press('Escape');
  await expect(page.locator('dialog[open]')).toHaveCount(1);
  await page.keyboard.press('Escape');
  await expect(page.locator('dialog[open]')).toHaveCount(0);
  await expect
    .poll(async () =>
      Math.abs((await sample.evaluate((node) => node.getBoundingClientRect().top)) - top),
    )
    .toBeLessThan(2);
});

test('phone contents transfer keyboard focus and keep the close control usable in a short viewport', async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 450 });
  await prepare(page);
  await page.goto('/article-public.html');
  const opener = page.getByRole('button', { name: 'Contents', exact: true });
  await opener.click();
  const dialog = page.locator('dialog[open]');
  await dialog.getByRole('button', { name: '展开全部', exact: true }).click();
  await dialog.locator('.pagenest-toc nav a').last().scrollIntoViewIfNeeded();
  const close = dialog.locator('[data-pagenest-dialog-close]');
  await expect(close).toBeInViewport();
  expect(
    await close.evaluate((node) => {
      const rect = node.getBoundingClientRect();
      return node.contains(
        document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2),
      );
    }),
  ).toBe(true);
  await close.click();
  await expect(opener).toBeFocused();
  await opener.click();
  const link = dialog.locator('.pagenest-toc nav a').first();
  const id = decodeURIComponent((await link.getAttribute('href')).slice(1));
  await link.focus();
  await page.keyboard.press('Enter');
  await expect(page.locator(`[id="${id}"]`)).toBeFocused();
  await expect(page.locator('dialog[open]')).toHaveCount(0);
  await page.keyboard.press('Tab');
  await expect(page.locator(`[id="${id}"]`)).not.toHaveAttribute('tabindex');
});

test('late load respects the teaching history view selected since DOM ready', async ({ page }) => {
  await page.addInitScript(() => {
    window.fixtureHistory = false;
    window.fixtureReveals = 0;
    window.MarkBridgeTeaching = {
      hasCurrentHistoryView: () => window.fixtureHistory,
      revealTarget: () => {
        window.fixtureReveals++;
      },
    };
  });
  let finish;
  const image = new Promise((resolve) => {
    finish = resolve;
  });
  await page.route('**/reading-delayed.svg', async (route) => {
    await image;
    await route.fulfill({
      contentType: 'image/svg+xml',
      body: '<svg xmlns="http://www.w3.org/2000/svg"/>',
    });
  });
  try {
    await prepare(page, true);
    await page.goto('/article-public.html#legacy-heading', { waitUntil: 'domcontentloaded' });
    await page.evaluate(() => {
      window.fixtureHistory = true;
    });
    const before = await page.evaluate(() => window.fixtureReveals);
    finish();
    await page.waitForLoadState('load');
    expect(await page.evaluate(() => window.fixtureReveals)).toBe(before);
  } finally {
    finish();
  }
});

test('an entirely collapsed article outline has no hidden current heading', async ({ page }) => {
  await page.goto('/article-public.html');
  await page.evaluate(() => {
    const body = document.querySelector('.pagenest-article-body');
    const fold = document.createElement('details');
    fold.className = 'wp-block-details';
    const summary = document.createElement('summary');
    summary.textContent = 'Additional explanation';
    fold.append(summary);
    fold.append(...body.querySelectorAll('h1,h2,h3,h4,h5,h6'));
    body.append(fold);
    document.dispatchEvent(new Event('mbb:teaching-change'));
  });
  await expect(page.locator('.pagenest-toc a[aria-current]')).toHaveCount(0);
});
