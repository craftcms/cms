const { test, expect } = require('@playwright/test');
const {injectAxe, checkA11y, getViolations} = require('axe-playwright');
const baseUrl = 'https://craft3.nitro/admin';

test.beforeEach(async ({ page }) => {
  // Runs before each test and signs in each page.
  await page.goto(`${baseUrl}/login`);
  await page.fill('#loginName', 'admin');
  await page.fill('#password', 'password');
  await page.click('#submit');
  await expect(page.locator('h1')).toHaveText('Dashboard');
});

test('Dashboard', async ({ page }) => {
  await page.goto(`${baseUrl}/dashboard`);
  await injectAxe(page);

  const violations = await getViolations(page, null, {
    axeOptions: {
      runOnly: {
        type: 'tag',
        values: ['wcag2a', 'wcag2aa'],
      },
    },
  });
  console.log(violations);
  expect(violations.length).toEqual(0);
});