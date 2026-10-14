const { test, expect } = require('@playwright/test');

for (const width of [1440, 390]) {
  test(`directory reveals hidden teaching targets without replacing headings at ${width}px`, async ({
    page,
  }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/article-public.html');
    await page.locator('.pagenest-toc').evaluate((node) => {
      node.open = true;
    });
    await page.getByRole('button', { name: '展开全部', exact: true }).click();
    const targetId = await page.evaluate(() => {
      const target = [
        ...document.querySelector('.pagenest-article-body').querySelectorAll('h1,h2,h3,h4,h5,h6'),
      ].at(-1);
      const stage = document.createElement('div');
      stage.id = 'synthetic-hidden-stage';
      target.before(stage);
      stage.append(target);
      stage.hidden = true;
      window.fixtureOriginalHeading = target;
      window.MarkBridgeTeaching = {
        revealTarget(node) {
          window.fixtureRevealed = node === target;
          if (node === target) {
            stage.hidden = false;
            document.dispatchEvent(new CustomEvent('mbb:teaching-change'));
          }
        },
      };
      scrollTo(0, 0);
      dispatchEvent(new Event('scroll'));
      return target.id;
    });
    const link = page.locator(`.pagenest-toc a[href="#${targetId}"]`);
    await expect(link).not.toHaveAttribute('aria-current', 'location');
    await link.click();
    await expect(page.locator('#synthetic-hidden-stage')).toBeVisible();
    expect(await page.evaluate(() => window.fixtureRevealed)).toBe(true);
    expect(
      await page.evaluate(
        (id) => document.getElementById(id) === window.fixtureOriginalHeading,
        targetId,
      ),
    ).toBe(true);
    expect(new URL(page.url()).hash).toBe('#' + targetId);
    await expect(page.locator(`[id="${targetId}"]`)).toBeInViewport();
    await page.screenshot({ path: `.runtime/teaching-directory-${width}.png` });
  });

  test(`generated headings and restored teaching history stay outside directory reveal at ${width}px`, async ({
    page,
  }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.route('**/article-public.html', async (route) => {
      const response = await route.fetch();
      await route.fulfill({
        response,
        body: (await response.text()).replace(
          '<div class="pagenest-article-body">',
          '<div class="pagenest-article-body"><div data-mbb-generated><h2>Runtime output</h2></div><div data-pagenest-generated><h2>Generated navigation</h2></div>',
        ),
      });
    });
    await page.goto('/article-public.html');
    await expect(page.locator('.pagenest-toc a', { hasText: 'Runtime output' })).toHaveCount(0);
    await expect(page.locator('.pagenest-toc a', { hasText: 'Generated navigation' })).toHaveCount(
      0,
    );
    const id = await page.evaluate(() => {
      const authored = [...document.querySelectorAll('.pagenest-article-body h2')].find(
        (node) => !node.closest('[data-mbb-generated],[data-pagenest-generated]'),
      );
      authored.hidden = true;
      window.fixtureRevealCount = 0;
      window.MarkBridgeTeaching = {
        hasCurrentHistoryView: () => true,
        revealTarget(target) {
          if (target === authored) {
            window.fixtureRevealCount++;
            authored.hidden = false;
          }
        },
      };
      history.replaceState({}, '', '#' + authored.id);
      dispatchEvent(new Event('hashchange'));
      return authored.id;
    });
    expect(await page.evaluate(() => window.fixtureRevealCount)).toBe(0);
    await expect(page.locator(`[id="${id}"]`)).toBeHidden();
    await page.locator('.pagenest-toc').evaluate((node) => {
      node.open = true;
    });
    await page.locator(`.pagenest-toc a[href="#${id}"]`).click();
    await expect(page.locator(`[id="${id}"]`)).toBeVisible();
    expect(await page.evaluate(() => window.fixtureRevealCount)).toBe(1);
  });
}
