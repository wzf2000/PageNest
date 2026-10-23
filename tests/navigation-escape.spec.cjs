const { test, expect } = require('@playwright/test');

async function setup(page, width) {
  await page.setViewportSize({ width, height: 900 });
  await page.goto('/editorial-article.html');
  await page.locator('.pagenest-article-body').evaluate((body) => {
    body.insertAdjacentHTML('beforeend', '<textarea id="comment" aria-label="Comment"></textarea>');
  });
  await page.locator('#pagenest-nav').evaluate((nav) => {
    nav.insertAdjacentHTML(
      'beforeend',
      '<details class="pagenest-dropdown"><summary>More</summary><div class="pagenest-dropdown-links"><a href="#legacy-heading">Chapter</a></div></details>',
    );
  });
}

for (const width of [390, 1440]) {
  test(`Escape belongs to the focused open navigation or search at ${width}px`, async ({
    page,
  }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await setup(page, width);
    await page.evaluate(() =>
      document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true })),
    );
    expect(errors).toEqual([]);
    const toggle = page.locator('.pagenest-menu-toggle');
    const comment = page.locator('#comment');
    const dropdown = page.locator('.pagenest-dropdown');
    const search = page.locator('.pagenest-header-search');
    await comment.fill('An ordinary comment');
    await page.keyboard.press('Escape');
    await expect(comment).toBeFocused();
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    if (width === 390) await toggle.click();
    else await dropdown.locator('summary').click();
    await comment.focus();
    await page.keyboard.press('Escape');
    await expect(comment).toBeFocused();
    if (width === 390) {
      await expect(toggle).toHaveAttribute('aria-expanded', 'true');
      await page.locator('#pagenest-nav > a').first().focus();
      await page.keyboard.press('Escape');
      await expect(toggle).toHaveAttribute('aria-expanded', 'false');
      await expect(toggle).toBeFocused();
    } else {
      await expect(dropdown).toHaveAttribute('open');
      await dropdown.locator('a').focus();
      await page.keyboard.press('Escape');
      await expect(dropdown).not.toHaveAttribute('open');
      await expect(dropdown.locator('summary')).toBeFocused();
    }
    await search.locator(':scope > summary').click();
    await expect(search.locator('input')).toBeFocused();
    await comment.focus();
    await page.keyboard.press('Escape');
    await expect(comment).toBeFocused();
    await expect(search).toHaveAttribute('open');
    await search.locator('input').focus();
    await page.keyboard.press('Escape');
    await expect(search).not.toHaveAttribute('open');
    await expect(search.locator(':scope > summary')).toBeFocused();
  });

  test(`navigation and search defer Escape to composition and nested controls at ${width}px`, async ({
    page,
  }) => {
    await setup(page, width);
    const toggle = page.locator('.pagenest-menu-toggle');
    const dropdown = page.locator('.pagenest-dropdown');
    const search = page.locator('.pagenest-header-search');
    for (const owner of ['navigation', 'search']) {
      if (owner === 'search') await search.locator(':scope > summary').click();
      else {
        if (width === 390) await toggle.click();
        await dropdown.locator('summary').click();
      }
      const field = owner === 'search' ? search.locator('input') : dropdown.locator('a');
      await field.focus();
      for (const mode of ['composing', '229', 'prevented']) {
        await field.evaluate((node, mode) => {
          const event = new KeyboardEvent('keydown', {
            key: 'Escape',
            bubbles: true,
            cancelable: true,
            isComposing: mode === 'composing',
            keyCode: mode === '229' ? 229 : 27,
          });
          if (mode === 'prevented')
            node.addEventListener('keydown', (event) => event.preventDefault(), { once: true });
          node.dispatchEvent(event);
        }, mode);
        await expect(field).toBeFocused();
        if (owner === 'search') await expect(search).toHaveAttribute('open');
        else if (width === 390) await expect(toggle).toHaveAttribute('aria-expanded', 'true');
        else await expect(dropdown).toHaveAttribute('open');
      }
      const container = owner === 'search' ? search : page.locator('#pagenest-nav');
      await container.evaluate((node) =>
        node.insertAdjacentHTML(
          'beforeend',
          '<div role="dialog" aria-modal="true"><input aria-label="Nested dialog"></div>',
        ),
      );
      const nested = container.getByRole('textbox', { name: 'Nested dialog' });
      await nested.focus();
      await page.keyboard.press('Escape');
      await expect(nested).toBeFocused();
      if (owner === 'search') await expect(search).toHaveAttribute('open');
      else if (width === 390) await expect(toggle).toHaveAttribute('aria-expanded', 'true');
      else await expect(dropdown).toHaveAttribute('open');
      await container.locator('[role="dialog"]').evaluate((node) => node.remove());
      await field.focus();
      await page.keyboard.press('Escape');
    }
  });
}
