/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

// Field values asserted here come from the workbench seeder
// (workbench/database/seeders/DatabaseSeeder.php), which installs Craft with
// the `craftcms` admin (user 1).
const username = 'craftcms';
const email = 'support@craftcms.com';

const saveButton = (page) => page.getByRole('button', {name: 'Save', exact: true});
const statusNotice = (page, text) => page.getByRole('status').filter({hasText: text});

test.beforeEach(async ({page}) => {
  await page.goto('./myaccount');
  // Wait for the form to hydrate before interacting with it
  await page.locator('#username').waitFor();
});

test.describe('Profile form', () => {
  test('shows the signed-in user’s identity', async ({page}) => {
    await expect(page.locator('h1')).toHaveText('My Account');
    await expect(page.locator('#username')).toHaveValue(username);
    await expect(page.locator('#email')).toHaveValue(email);
    await expect(saveButton(page)).toBeVisible();
  });

  test('saving an edited full name persists it', async ({page}) => {
    const fullName = 'Playwright Full Name';

    try {
      // Clear first: pressSequentially appends, and an interrupted earlier
      // run may have left a value behind
      await page.locator('#fullName').fill('');
      await page.locator('#fullName').pressSequentially(fullName, {delay: 50});
      await saveButton(page).click();

      // The result is announced accessibly
      await expect(statusNotice(page, 'User saved.')).toBeVisible();

      // The change survives a fresh page load
      await page.reload();
      await expect(page.locator('#fullName')).toHaveValue(fullName);
    } finally {
      // Restore the seeded state so other tests and reruns start clean
      await page.locator('#fullName').fill('');
      await saveButton(page).click();
      await expect(statusNotice(page, 'User saved.')).toBeVisible();
    }
  });

  test('rejects an empty username without applying it', async ({page}) => {
    await page.locator('#username').fill('');
    await saveButton(page).click();

    // Curly apostrophe in “Couldn’t” — match loosely
    await expect(statusNotice(page, /Couldn.t save user\./)).toBeVisible();

    // The rejected change must not stick
    await page.reload();
    await expect(page.locator('#username')).toHaveValue(username);
  });
});

test.describe('Account navigation', () => {
  test('lists the account sections', async ({page}) => {
    const nav = page.getByRole('navigation', {name: 'Secondary'});

    for (const section of [
      'Profile',
      'Preferences',
      'Addresses',
      'Password & Verification',
      'Passkeys',
    ]) {
      await expect(nav.getByRole('link', {name: section})).toBeVisible();
    }
  });
});
