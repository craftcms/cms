const {test, expect} = require('@playwright/test');

test('Shoud show a category page', async ({page, context, baseURL}) => {
  await page.goto(baseURL + '/plugin-store/categories/23');

  // Wait for category request listing plugins to be done
  await page.waitForResponse((response) =>
    response.url().includes('//api.craftcms.com/v1/plugin-store/plugins')
  );

  // Category title
  const title = page.locator('.ps-wrapper h1');
  await expect(title).toHaveText('SEO & Accessibility');

  // Plugins
  const pluginsLength = await page.locator('.plugin-card').count();
  expect(pluginsLength > 0).toBeTruthy();
});
