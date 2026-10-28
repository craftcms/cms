/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test('Shoud show the Discover page', async ({page}) => {
  await page.goto('./plugin-store');
  await expect(page.getByRole('heading', {level: 1})).toHaveText(
    'Plugin Store'
  );
});

test('Should show featured plugins', async ({page}) => {
  await page.goto('./plugin-store');

  await expect(page.getByRole('link', {name: 'See all'}).first()).toBeVisible();
  await expect(
    page.locator('.plugin-grid').getByRole('link').first()
  ).toBeVisible();
});

test('Should list the categories in the sidebar', async ({page}) => {
  await page.goto('./plugin-store');

  await expect(page.getByRole('link', {name: 'Discover'})).toBeVisible();
  await expect(
    page.getByRole('link', {name: 'SEO & Accessibility'})
  ).toBeVisible();
});
