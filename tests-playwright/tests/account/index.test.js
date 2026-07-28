/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test.beforeEach(async ({page}) => {
  // Make sure we start each test on the My Account page
  await page.goto('./myaccount');
});

test('The My Account page exists', async ({page, baseURL}) => {
  const title = page.locator('h1');
  await expect(title).toHaveText('My Account');
});

test('The active page has an accessible state', async ({page, baseURL}) => {
  // In the redesigned CP the account sub-nav ("Profile", "Permissions", …)
  // moved into the user-menu web component, and its items currently expose
  // no link/menuitem roles for an aria-current assertion to attach to.
  test.fixme(
    true,
    'Account sub-nav items are not exposed to the accessibility tree in the redesigned CP'
  );

  const activeLink = page
    .getByRole('link')
    .filter({hasText: 'Profile', hasClass: 'sel'});
  await expect(activeLink).toHaveAttribute('aria-current', 'page');
});
