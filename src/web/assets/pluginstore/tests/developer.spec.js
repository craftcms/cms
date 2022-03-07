const {test, expect} = require('@playwright/test');

test('Should show developer details', async ({ page, baseURL }) => {
  await page.goto(baseURL + '/plugin-store/developer/70');

  // Wait for category request listing plugins to be done
  await page.waitForResponse(response => response.url().includes('//api.craftcms.com/v1/plugin-store/plugins'))

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
