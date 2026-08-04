/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test('Should show the cart', async ({page, baseURL}) => {
  await page.goto('./plugin-store');
  await page.click('#cart-button');

  const title = page.locator('#pluginstore-modal h1');
  await expect(title).toHaveText('Cart');
});
