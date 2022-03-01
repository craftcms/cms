const {test, expect} = require('@playwright/test');

test('Should show developer details', async ({ page, baseURL }) => {
  await page.goto(baseURL + '/plugin-store/developer/70');

  // Developer name
  const title = page.locator('.ps-wrapper h1');
  await expect(title).toContainText('Pixel & Tonic');

  // Developer website
  const website = page.locator('.developer-card ul li a');
  await expect(website).toContainText('Website');

  // Plugins
  const pluginsLength = await page.locator('.plugin-card').count();
  expect((pluginsLength > 0)).toBeTruthy();
});
