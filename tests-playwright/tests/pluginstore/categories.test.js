/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test('Shoud show a category page', async ({page}) => {
  await page.goto('./plugin-store/categories/23');

  await expect(page.getByRole('heading', {level: 1})).toHaveText(
    'SEO & Accessibility'
  );
  await expect(
    page.locator('.plugin-grid').getByRole('link').first()
  ).toBeVisible();
});
