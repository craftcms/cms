const {test, expect} = require('@playwright/test');
const {waitForPluginStore} = require('./.playwright/utils.js');

const waitForDiscoverPage = async ({page}) => {
  await Promise.all([
    // Wait for features sections to be loaded
    page.waitForResponse((response) =>
      response
        .url()
        .includes('//api.craftcms.com/v1/plugin-store/featured-sections')
    ),

    // Wait for active trials to be loaded
    page.waitForResponse((response) =>
      response.url().includes('//api.craftcms.com/v1/cms-editions')
    ),
    page.waitForResponse((response) =>
      response
        .url()
        .includes('//api.craftcms.com/v1/plugin-store/plugins-by-handles')
    ),
  ]);
};

test('Shoud show the Discover page', async ({page, baseURL}) => {
  await page.goto(baseURL + '/plugin-store');
  const title = page.locator('h1');
  await expect(title).toHaveText('Plugin Store');
});

test('Should show featured plugins', async ({page, baseURL}) => {
  await page.goto(baseURL + '/plugin-store');

  await waitForPluginStore({page});
  await waitForDiscoverPage({page});

  // Check that the page shows featured sections
  const featuredSectionsLength = await page
    .locator('.featured-section')
    .count();
  expect(featuredSectionsLength > 0).toBeTruthy();

  // Check that the page shows plugins
  const pluginsLength = await page.locator('.plugin-card').count();
  expect(pluginsLength > 0).toBeTruthy();
});
