const {test, expect} = require('@playwright/test');

test('Should show plugin details', async ({page, baseURL}) => {
  await page.goto(baseURL + '/plugin-store/sherlock');

  // Wait plugin request to be done
  await page.waitForResponse((response) =>
    response
      .url()
      .includes('//api.craftcms.com/v1/plugin-store/plugin/sherlock')
  );

  // Developer name
  const title = page.locator('.plugin-details-header h1');
  await expect(title).toContainText('Sherlock');

  // Developer name
  const website = page.locator('.plugin-details-header .developer');
  await expect(website).toContainText('PutYourLightsOn');

  // Tabs
  const tabsLength = await page
    .locator('.plugin-details-header .tabs ul li')
    .count();
  expect(tabsLength === 3).toBeTruthy();

  const tabs = page.locator('.plugin-details-header .tabs');
  await expect(tabs).toContainText('Overview');
  await expect(tabs).toContainText('Pricing');
  await expect(tabs).toContainText('Changelog');
});

test('Plugin details should have links to categories', async ({
  page,
  baseURL,
}) => {
  await page.goto(baseURL + '/plugin-store/sherlock');

  // Wait plugin request to be done
  await page.waitForResponse((response) =>
    response
      .url()
      .includes('//api.craftcms.com/v1/plugin-store/plugin/sherlock')
  );

  // Click category
  await page.click('.meta-categories a:text("Security")');

  await page.waitForResponse((response) =>
    response.url().includes('//api.craftcms.com/v1/plugin-store/plugins')
  );

  // Category title
  const title = page.locator('.ps-wrapper h1');
  await expect(title).toHaveText('Security');
});
