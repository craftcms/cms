/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test('Should show developer details', async ({page}) => {
  await page.goto('./plugin-store/developer/989727');

  await expect(page.getByRole('heading', {level: 1})).toContainText(
    'PutYourLightsOn'
  );
  await expect(page.getByRole('link', {name: /Website/})).toBeVisible();
  await expect(
    page.locator('.plugin-grid').getByRole('link').first()
  ).toBeVisible();
});
