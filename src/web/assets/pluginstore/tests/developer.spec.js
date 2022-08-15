const {test, expect} = require('@playwright/test');

test('Should show developer details', async ({page, baseURL}) => {
  await page.goto(baseURL + '/plugin-store/developer/610');

  // Wait for developer request to be done
  await page.waitForResponse((response) =>
    response.url().includes('//api.craftcms.com/v1/developer/610')
  );

  // Wait for the plugins request to be done
  await page.waitForResponse((response) =>
    response.url().includes('//api.craftcms.com/v1/plugin-store/plugins')
  );

  // Developer name
  const title = page.locator('.ps-wrapper h1');
  await expect(title).toContainText('PutYourLightsOn');

  // Developer website
  const website = page.locator('.developer-card .developer-buttons a');
  await expect(website).toContainText('Website');

  // Plugins
  const pluginsLength = await page.locator('.plugin-card').count();
  expect(pluginsLength > 0).toBeTruthy();
});
