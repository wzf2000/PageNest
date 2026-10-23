const { test, expect } = require('@playwright/test');
const storageKey = 'pagenest-reader-preferences-v1';
const controls = '[data-pagenest-reader-preferences]';
const article = '.pagenest-reading-grid .pagenest-article';
const body = `${article} .pagenest-article-body`;
async function open(page, fixture = 'article-public') {
  await page.goto(`/${fixture}.html`);
  await expect(page.locator(controls)).toBeVisible();
  await page.locator(article).evaluate((node) => {
    if (!node.querySelector('.pagenest-cover')) {
      const cover = document.createElement('figure');
      cover.className = 'pagenest-cover';
      const image = document.createElement('img');
      image.alt = 'Synthetic illustration';
      image.src =
        'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22%3E%3Crect width=%2250%22 height=%2250%22 fill=%22red%22/%3E%3C/svg%3E';
      cover.append(image);
      node.querySelector('.pagenest-article-body').before(cover);
    }
    if (!document.getElementById('comments')) {
      const comments = document.createElement('div');
      comments.id = 'comments';
      comments.className = 'pagenest-comments';
      comments.textContent = 'Synthetic comments';
      node.after(comments);
    }
  });
  await page.locator(`${controls} summary`).click();
}
async function select(page, name, value) {
  await page.locator(`[data-reader-preference="${name}"][value="${value}"]`).check();
}
for (const width of [1440, 390]) {
  test(`reader preferences persist and reset at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await open(page);
    const defaultSize = await page
      .locator(body)
      .evaluate((node) => getComputedStyle(node).fontSize);
    const defaultWidth = await page
      .locator(body)
      .evaluate((node) => node.getBoundingClientRect().width);
    const defaultColor = await page.locator(body).evaluate((node) => getComputedStyle(node).color);
    const imageStyle = await page.locator('.pagenest-cover img').evaluate((node) => ({
      filter: getComputedStyle(node).filter,
      opacity: getComputedStyle(node).opacity,
    }));
    const commentColor = await page
      .locator('#comments')
      .evaluate((node) => getComputedStyle(node).color);
    for (const size of ['18', '20', '22']) {
      await select(page, 'size', size);
      await expect(page.locator(body)).toHaveCSS('font-size', `${size}px`);
    }
    const defaultUsable = await page.locator(body).evaluate((node) => {
      const css = getComputedStyle(node);
      return node.clientWidth - parseFloat(css.paddingLeft) - parseFloat(css.paddingRight);
    });
    await select(page, 'width', 'compact');
    const compactWidth = await page
      .locator(body)
      .evaluate((node) => node.getBoundingClientRect().width);
    expect(compactWidth).toBeLessThanOrEqual(680);
    expect(compactWidth).toBeLessThanOrEqual(defaultWidth);
    const compactUsable = await page.locator(body).evaluate((node) => {
      const css = getComputedStyle(node);
      return node.clientWidth - parseFloat(css.paddingLeft) - parseFloat(css.paddingRight);
    });
    expect(compactUsable).toBeLessThan(defaultUsable);
    await select(page, 'palette', 'dark');
    await expect(page.locator(body)).toHaveCSS('color', 'rgb(220, 227, 236)');
    await expect(page.locator(`${body} td`).first()).toHaveCSS('color', 'rgb(220, 227, 236)');
    await expect(page.locator(`${body} h2 .mbb-math`).first()).toHaveCSS(
      'color',
      'rgb(241, 245, 250)',
    );
    if (width === 390) {
      await expect(page.locator('.pagenest-mobile-toc summary')).toHaveCSS(
        'color',
        'rgb(241, 245, 250)',
      );
      await expect(page.locator('.pagenest-mobile-toc .mbb-math').first()).toHaveCSS(
        'color',
        'rgb(165, 205, 248)',
      );
    }
    expect(await page.locator('#comments').evaluate((node) => getComputedStyle(node).color)).toBe(
      commentColor,
    );
    expect(
      await page.locator('.pagenest-cover img').evaluate((node) => ({
        filter: getComputedStyle(node).filter,
        opacity: getComputedStyle(node).opacity,
      })),
    ).toEqual(imageStyle);
    expect(
      await page.evaluate(() => JSON.parse(localStorage.getItem('pagenest-reader-preferences-v1'))),
    ).toEqual({
      version: 1,
      preferences: { size: '22', width: 'compact', palette: 'dark' },
    });
    await page.reload();
    await expect(page.locator(body)).toHaveCSS('font-size', '22px');
    await expect(page.locator(article)).toHaveAttribute('data-reader-width', 'compact');
    await page.locator(`${controls} summary`).click();
    await select(page, 'width', 'wide');
    const wideUsable = await page.locator(body).evaluate((node) => {
      const css = getComputedStyle(node);
      return node.clientWidth - parseFloat(css.paddingLeft) - parseFloat(css.paddingRight);
    });
    expect(wideUsable).toBeGreaterThan(defaultUsable);
    await select(page, 'palette', 'paper');
    await expect(page.locator(body)).toHaveCSS('color', 'rgb(73, 61, 48)');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
    await page.getByRole('button', { name: '恢复默认', exact: true }).click();
    await expect(page.locator(body)).toHaveCSS('font-size', defaultSize);
    await expect(page.locator(body)).toHaveCSS('color', defaultColor);
    expect(await page.locator(body).evaluate((node) => node.getBoundingClientRect().width)).toBe(
      defaultWidth,
    );
    expect(await page.evaluate((key) => localStorage.getItem(key), storageKey)).toBeNull();
    await page.reload();
    await expect(page.locator(body)).toHaveCSS('font-size', defaultSize);
    expect(errors).toEqual([]);
  });

  test(`width survives extension panel changes at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    await open(page, 'article-integrated');
    for (const value of ['default', 'compact', 'wide']) {
      await select(page, 'width', value);
      const before = await page
        .locator(body)
        .evaluate((node) => node.getBoundingClientRect().width);
      await page.locator('.pagenest-reading-panel').evaluate((panel) => {
        panel.hidden = false;
        panel.innerHTML = '<p style="width:1000px">Synthetic comment panel</p>';
        panel.closest('.pagenest-reading-rail').classList.add('pagenest-panel-active');
      });
      await page.locator('#comments').evaluate((comments) => {
        comments.innerHTML +=
          '<div style="overflow:auto"><p style="width:1000px">Synthetic comment</p></div>';
      });
      expect(await page.locator(body).evaluate((node) => node.getBoundingClientRect().width)).toBe(
        before,
      );
      await expect(page.locator('.pagenest-toc nav a')).toHaveCount(18);
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
        true,
      );
      await page.locator('.pagenest-reading-panel').evaluate((panel) => {
        panel.hidden = true;
        panel.closest('.pagenest-reading-rail').classList.remove('pagenest-panel-active');
      });
    }
  });
}

test('keyboard controls and responsive placement preserve selection and anchors', async ({
  page,
}) => {
  await page.goto('/article-public.html');
  const summary = page.locator(`${controls} summary`);
  await summary.focus();
  await page.keyboard.press('Enter');
  await expect(page.locator(controls)).toHaveAttribute('open', '');
  await page.keyboard.press('Tab');
  await expect(page.locator('[data-reader-preference="size"][value="default"]')).toBeFocused();
  await page.keyboard.press('ArrowRight');
  await page.keyboard.press('ArrowRight');
  await page.keyboard.press('ArrowRight');
  await page.keyboard.press('Tab');
  await expect(page.locator(body)).toHaveCSS('font-size', '22px');
  await page.setViewportSize({ width: 390, height: 900 });
  await expect(
    page.locator('.pagenest-article-column > [data-pagenest-reader-preferences]'),
  ).toHaveCount(1);
  await expect(page.locator(controls)).toHaveAttribute('open', '');
  await expect(page.locator('.pagenest-article-body #legacy-heading')).toHaveCount(1);
  await page.setViewportSize({ width: 1440, height: 900 });
  await expect(
    page.locator('[data-pagenest-reading-actions] > [data-pagenest-reader-preferences]'),
  ).toHaveCount(1);
  await expect(page.locator(body)).toHaveCSS('font-size', '22px');
});

test('reset uses configured site defaults', async ({ page }) => {
  await open(page, 'article-appearance');
  await expect(page.locator(body)).toHaveCSS('font-size', '22px');
  await select(page, 'size', '18');
  await page.getByRole('button', { name: '恢复默认', exact: true }).click();
  await expect(page.locator(body)).toHaveCSS('font-size', '22px');
});

for (const stored of [
  '{bad json',
  JSON.stringify({ version: 2, preferences: { size: '22' } }),
  JSON.stringify({
    version: 1,
    preferences: { size: '999', width: '100vw', palette: 'evil', url: 'private' },
  }),
]) {
  test(`invalid storage falls back safely: ${stored.slice(0, 35)}`, async ({ page }) => {
    await page.addInitScript(({ key, value }) => localStorage.setItem(key, value), {
      key: storageKey,
      value: stored,
    });
    await open(page);
    for (const name of ['size', 'width', 'palette']) {
      await expect(
        page.locator(`[data-reader-preference="${name}"][value="default"]`),
      ).toBeChecked();
      await expect(page.locator(article)).not.toHaveAttribute(`data-reader-${name}`);
    }
    await select(page, 'size', '20');
    expect(await page.evaluate((key) => JSON.parse(localStorage.getItem(key)), storageKey)).toEqual(
      { version: 1, preferences: { size: '20', width: 'default', palette: 'default' } },
    );
  });
}

test('denied storage still applies and resets preferences', async ({ page }) => {
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  await page.addInitScript(() => {
    for (const method of ['getItem', 'setItem', 'removeItem']) {
      Storage.prototype[method] = () => {
        throw new DOMException('Denied', 'SecurityError');
      };
    }
  });
  await open(page);
  await select(page, 'size', '20');
  await expect(page.locator(body)).toHaveCSS('font-size', '20px');
  await expect(page.locator(`${controls} [role="status"]`)).toContainText('仅本页生效');
  await page.getByRole('button', { name: '恢复默认', exact: true }).click();
  await expect(page.locator(body)).toHaveCSS('font-size', '17px');
  expect(errors).toEqual([]);
});

test('no JavaScript keeps controls hidden and article readable', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('/article-public.html');
  await expect(page.locator(controls)).toBeHidden();
  await expect(page.locator(`${body} p`).first()).toBeVisible();
  await expect(page.locator(body)).toHaveCSS('font-size', '17px');
  await context.close();
});

test('settings are absent from non-article and password-protected fixtures', async ({ page }) => {
  for (const fixture of ['home-configured', 'page-archives', 'article-password']) {
    await page.goto(`/${fixture}.html`);
    await expect(page.locator(controls)).toHaveCount(0);
  }
});

test('dark reading keeps code, math, metadata and concealed hints legible', async ({ page }) => {
  await open(page);
  await page.locator(body).evaluate((node) => {
    node.insertAdjacentHTML(
      'beforeend',
      '<pre><code><span class="token keyword" style="color:#000">const value = 1;</span></code></pre><p><code>inline code</code></p><div class="wp-block-mbb-math"><span class="mbb-math">x + y</span></div>',
    );
  });
  await select(page, 'palette', 'dark');
  await expect(page.locator(`${body} pre code .token`)).toHaveCSS('color', 'rgb(206, 225, 247)');
  await expect(page.locator(`${body} p > code`)).toHaveCSS('background-color', 'rgb(44, 55, 69)');
  await expect(page.locator(`${body} .wp-block-mbb-math .mbb-math`)).toHaveCSS(
    'color',
    'rgb(220, 227, 236)',
  );
  await expect(page.locator('.pagenest-article-header .pagenest-author a').first()).toHaveCSS(
    'color',
    'rgb(241, 245, 250)',
  );
  await expect(page.locator('.pagenest-author time').first()).toHaveCSS(
    'color',
    'rgb(177, 189, 204)',
  );
  await expect(page.locator('.pagenest-article-footer')).toHaveCSS(
    'background-color',
    'rgb(44, 55, 69)',
  );
  for (const palette of ['paper', 'dark']) {
    await select(page, 'palette', palette);
    const hint = await page.locator(`${body} .pagenest-exercise-hint`).evaluate((node) => {
      const css = getComputedStyle(node);
      return { color: css.color, background: css.backgroundColor };
    });
    expect(hint.color).toBe(hint.background);
  }
});

test('larger option labels stay readable and compact in both languages on a narrow screen', async ({
  page,
}) => {
  await page.setViewportSize({ width: 320, height: 844 });
  for (const fixture of ['article-public', 'article-english']) {
    await open(page, fixture);
    const panel = page.locator(controls);
    const normalHeight = (await panel.boundingBox()).height;
    await select(page, 'palette', 'paper');
    await expect(panel.locator('[role=status]')).toHaveCSS('font-size', '13px');
    for (const choice of await panel.locator('.pagenest-reader-choice').all()) {
      await expect(choice).toHaveCSS('font-size', '14px');
      const geometry = await choice.evaluate((node) => {
        const bounds = node.getBoundingClientRect();
        const label = node.querySelector('span:last-child');
        const range = document.createRange();
        range.selectNodeContents(label);
        return {
          height: bounds.height,
          fits: [...range.getClientRects()].every(
            (rect) =>
              rect.left >= bounds.left &&
              rect.right <= bounds.right &&
              rect.top >= bounds.top &&
              rect.bottom <= bounds.bottom,
          ),
        };
      });
      expect(geometry.height).toBeGreaterThanOrEqual(44);
      expect(geometry.fits).toBe(true);
    }
    expect((await panel.boundingBox()).height).toBeLessThanOrEqual(normalHeight);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
  }
});
