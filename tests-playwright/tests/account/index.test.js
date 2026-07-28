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
  // The nav items are `craft-nav-item` web components whose label is slotted;
  // locate the link by its accessible name (which resolves through the slot).
  const activeLink = page
    .getByRole('navigation', {name: 'Secondary'})
    .getByRole('link', {name: 'Profile'});
  await expect(activeLink).toHaveAttribute('aria-current', 'page');
});
