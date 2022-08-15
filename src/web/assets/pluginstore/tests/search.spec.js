// Should search plugins and get results
// Should search plugins and get no results

const {test, expect} = require('@playwright/test');

test('Should search plugins and get results', async ({
  page,
  context,
  baseURL,
}) => {
  await page.goto(baseURL + '/plugin-store');

  // Search for “commerce” plugins
  await page.fill('#searchQuery', 'commerce');
  await page.keyboard.press('Enter');

  // Results
  const title = page.locator('.ps-wrapper h1');
  await expect(title).toHaveText('Showing results for “commerce”');

  // Wait for search request to be done
  await page.waitForResponse((response) =>
    response.url().includes('//api.craftcms.com/v1/plugin-store/plugins')
  );

  // Plugins
  const pluginsLength = await page.locator('.plugin-card').count();
  expect(pluginsLength > 0).toBeTruthy();
});

test('Should search plugins and get no results', async ({
  page,
  context,
  baseURL,
}) => {
  await page.goto(baseURL + '/plugin-store');

  // Search for “commerce” plugins
  await page.fill('#searchQuery', 'query-with-no-results');
  await page.keyboard.press('Enter');

  // Results
  const title = page.locator('.ps-wrapper h1');
  await expect(title).toHaveText('Showing results for “query-with-no-results”');

  // Wait for search request to be done
  await page.waitForResponse((response) =>
    response.url().includes('//api.craftcms.com/v1/plugin-store/plugins')
  );

  // Plugins
  const pluginsLength = await page.locator('.plugin-card').count();
  expect(pluginsLength === 0).toBeTruthy();
});
