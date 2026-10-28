/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test('Should show the cart', async ({page}) => {
  await page.goto('./plugin-store');
  await page.getByRole('button', {name: /^Cart/}).click();

  await expect(page.getByRole('dialog', {name: 'Cart'})).toBeVisible();
  await expect(
    page.getByRole('heading', {name: 'Items in your cart'})
  ).toBeVisible();
});

test('Should show the Upgrade Craft CMS page', async ({page}) => {
  await page.goto('./plugin-store/upgrade-craft');

  await expect(page.getByRole('heading', {level: 1})).toHaveText(
    'Upgrade Craft CMS'
  );
  await expect(page.getByRole('heading', {name: 'Pro'})).toBeVisible();
});
