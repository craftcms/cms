const {test, expect} = require('@playwright/test');

test('Shoud show the Discover page', async ({ page, context, baseURL }) => {
  await page.goto(baseURL + '/plugin-store/categories/seo');

  // Category title
  const title = page.locator('.ps-wrapper h1');
  await expect(title).toHaveText('SEO & Accessibility');

  // Plugins
  const pluginsLength = await page.locator('.plugin-card').count();
  expect((pluginsLength > 0)).toBeTruthy();
});
