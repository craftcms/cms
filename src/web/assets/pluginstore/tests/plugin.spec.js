const {test, expect} = require('@playwright/test');

test('Should show developer details', async ({ page, baseURL }) => {
  await page.goto(baseURL + '/plugin-store/commerce');

  // Developer name
  const title = page.locator('.plugin-details-header h1');
  await expect(title).toContainText('Craft Commerce');

  // Developer name
  const website = page.locator('.plugin-details-header .developer');
  await expect(website).toContainText('Pixel & Tonic');

  // Tabs
  const tabsLength = await page.locator('.plugin-details-header .tabs ul li').count();
  expect((tabsLength === 3)).toBeTruthy();

  const tabs = page.locator('.plugin-details-header .tabs');
  await expect(tabs).toContainText('Overview');
  await expect(tabs).toContainText('Pricing');
  await expect(tabs).toContainText('Changelog');
});
