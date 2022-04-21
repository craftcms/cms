const {test, expect} = require('@playwright/test');

test('Should show the cart', async ({page, baseURL}) => {
  await page.goto(baseURL + '/plugin-store');
  await page.click('#cart-button');

  const title = page.locator('#pluginstore-modal h1');
  await expect(title).toHaveText('Cart');
});
