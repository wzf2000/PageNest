const { test, expect } = require('@playwright/test');

for (const width of [1440, 390]) {
  test(`published quotes retain a readable boundary without editor styles at ${width}px`, async ({
    page,
  }) => {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/article-public.html');
    // The shared historical fixture loads editor CSS; published WordPress posts do not.
    await page.locator('link[href*="/content-"]').evaluateAll((nodes) => {
      nodes.forEach((node) => node.remove());
    });
    await expect(page.locator('link[href*="/content-"]')).toHaveCount(0);
    await expect(page.locator('.pagenest-header')).toHaveAttribute(
      'data-mbb-teaching-sticky-obstruction',
      '',
    );
    await page.locator('.pagenest-article-body').evaluate((body) => {
      const quote = document.createElement('blockquote');
      quote.className = 'wp-block-quote';
      quote.innerHTML =
        '<p id="quote-paragraph">引用说明：保留文字、列表、公式和内部链接。</p>' +
        '<ul><li>第一种情况</li><li>第二种情况</li></ul>' +
        '<blockquote><p>嵌套引用</p></blockquote>';
      body.prepend(quote);
    });
    const quote = page.locator('.pagenest-article-body > blockquote');
    await expect(quote).toHaveCSS('border-left-width', '3px');
    expect(await quote.evaluate((node) => getComputedStyle(node).backgroundColor)).not.toBe(
      await quote.evaluate((node) => getComputedStyle(node.parentElement).backgroundColor),
    );
    expect(await quote.evaluate((node) => node.scrollWidth <= node.clientWidth + 1)).toBe(true);
    await page.locator('.pagenest-article-body').evaluate((body) => (body.dir = 'rtl'));
    await expect(quote).toHaveCSS('border-right-width', '3px');
    await expect(quote).toHaveCSS('border-left-width', '0px');
    await page.emulateMedia({ media: 'print' });
    await expect(page.locator('#quote-paragraph')).toBeVisible();
    await expect(quote).toHaveCSS('border-right-width', '3px');
  });
}
