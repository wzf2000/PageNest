const { test, expect } = require('@playwright/test');
for (const width of [1440, 390]) {
  test(`templates and navigation at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    await page.route('**/*', (route) =>
      new URL(route.request().url()).origin === 'http://127.0.0.1:18779'
        ? route.continue()
        : route.abort(),
    );
    for (const name of ['home-configured', 'home-empty', 'article-public', 'article-integrated']) {
      await page.goto(`/${name}.html`);
      await expect(page.locator('.pagenest-footer > div')).toHaveCount(2);
      expect(await page.evaluate(() => window.injected)).toBeUndefined();
      expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(
        false,
      );
      if (width < 1024) {
        await page.locator('.pagenest-menu-toggle').click();
        await expect(page.locator('.pagenest-menu-toggle')).toHaveAttribute(
          'aria-expanded',
          'true',
        );
        await page.keyboard.press('Escape');
        await expect(page.locator('.pagenest-menu-toggle')).toHaveAttribute(
          'aria-expanded',
          'false',
        );
      } else await expect(page.locator('#pagenest-nav')).toBeVisible();
      if (name.startsWith('article')) {
        await expect(page.locator('.pagenest-toc nav a')).toHaveCount(18);
        await expect(page.locator('.pagenest-toc [data-mbb-tex="x^2"]')).toHaveCount(6);
        await expect(page.locator('.pagenest-toc nav button')).toHaveCount(12);
        expect(await page.locator('.pagenest-toc nav a').first().textContent()).not.toContain(
          'ignore',
        );
        await expect(page.locator('.pagenest-article-body #legacy-heading')).toHaveCount(1);
        await expect(page.locator('.pagenest-reading-panel')).toHaveCount(1);
        await expect(page.locator('.pagenest-table-scroll > table')).toHaveCount(1);
        await expect(page.locator('.pagenest-exercise-hint')).toHaveCSS(
          'color',
          'rgb(255, 255, 255)',
        );
        await page
          .locator('.pagenest-toc summary')
          .evaluate((el) => (el.parentElement.open = true));
        const toggles = page.locator('.pagenest-toc-toggle');
        await expect(toggles).toHaveCount(12);
        await expect(toggles.nth(1)).toHaveAttribute('aria-expanded', 'false');
        await page.getByRole('button', { name: '展开全部', exact: true }).click();
        expect(
          await toggles.evaluateAll((nodes) =>
            nodes.every((el) => el.getAttribute('aria-expanded') === 'true'),
          ),
        ).toBe(true);
        await page.getByRole('button', { name: '收起全部', exact: true }).click();
        expect(
          await toggles.evaluateAll((nodes) =>
            nodes.every((el) => el.getAttribute('aria-expanded') === 'false'),
          ),
        ).toBe(true);
        await toggles.first().click();
        await expect(toggles.first()).toHaveAttribute('aria-expanded', 'true');
        await page.locator('.pagenest-toc nav a').nth(1).click();
        expect(new URL(page.url()).hash).toBe('#pagenest-section-2');
      }
      if (name === 'article-integrated') {
        await expect(page.locator('.fixture-action')).toHaveCount(1);
        await page.evaluate(() => {
          document.dispatchEvent(new Event('pagenest-integration-ready'));
          document.dispatchEvent(new Event('pagenest-integration-ready'));
        });
        await expect(page.locator('[data-pagenest-header-actions] > .fixture-action')).toHaveCount(
          1,
        );
        await page.locator('.pagenest-reading-panel').evaluate((el) => {
          el.dataset.fixtureState = 'preserved';
          el.textContent = 'Extension state';
        });
        await page.setViewportSize({ width: width < 1024 ? 1440 : 390, height: 900 });
        await expect(page.locator('.pagenest-reading-panel')).toHaveAttribute(
          'data-fixture-state',
          'preserved',
        );
        await expect(page.locator('.pagenest-reading-panel')).toHaveText('Extension state');
        await page.setViewportSize({ width, height: 900 });
      }
      if (name === 'home-configured')
        await expect(page.locator('.pagenest-footer > div:first-child > p')).toHaveText(
          '<script>Tagline</script>',
        );
    }
    expect(errors).toEqual([]);
  });
}

for (const width of [1440, 390]) {
  test(`configured aliases follow dynamic editor preview at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/editor-preview.html');
    const source = await page.locator('#source').inputValue();
    await expect(page.locator('.editormd-preview-container .fixture-hint')).toHaveCSS(
      'color',
      'rgb(255, 255, 255)',
    );
    await expect(page.locator('#outside-preview')).not.toHaveClass(/pagenest-exercise-hint/);
    await page.locator('.editormd-preview-container').evaluate((node) => {
      node.innerHTML =
        '<p><span class="fixture-hint">Dynamic hint</span><span class="fixture-invalid">Invalid mapping</span></p><table><tr><td>Dynamic table</td></tr></table>';
    });
    await expect(page.locator('.editormd-preview-container .fixture-hint')).toHaveClass(
      'fixture-hint pagenest-exercise-hint',
    );
    await expect(page.locator('.editormd-preview-container .fixture-hint')).toHaveCSS(
      'color',
      'rgb(255, 255, 255)',
    );
    await expect(page.locator('.fixture-invalid')).toHaveClass('fixture-invalid');
    await expect(page.locator('.pagenest-table-scroll > table')).toHaveCount(1);
    await expect(page.locator('#source')).toHaveValue(source);
    await page.evaluate(() => {
      delete window.PageNestContentAliases;
      document.querySelector('.editormd-preview-container').innerHTML =
        '<span class="fixture-hint">No configuration</span>';
    });
    await expect(page.locator('.editormd-preview-container .fixture-hint')).toHaveClass(
      'fixture-hint',
    );
    expect(errors).toEqual([]);
  });
}
