/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test('Should search plugins and get results', async ({page}) => {
  await page.goto('./plugin-store');

  await page.getByLabel('Search plugins').fill('commerce');
  await page.keyboard.press('Enter');

  await expect(page.getByRole('heading', {level: 1})).toHaveText(
    'Showing results for “commerce”'
  );
  await expect(
    page.locator('.plugin-grid').getByRole('link').first()
  ).toBeVisible();
});

test('Should search plugins and get no results', async ({page}) => {
  await page.goto('./plugin-store');

  await page.getByLabel('Search plugins').fill('query-with-no-results');
  await page.keyboard.press('Enter');

  await expect(page.getByRole('heading', {level: 1})).toHaveText(
    'Showing results for “query-with-no-results”'
  );
  await expect(page.getByText('No results.')).toBeVisible();
});
