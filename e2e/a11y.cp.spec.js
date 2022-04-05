const { test, expect } = require('@playwright/test');
const {injectAxe, checkA11y} = require('axe-playwright');

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
  await expect(page.locator('h1')).toHaveText('Dashboard');
  await injectAxe(page);
  await checkA11y(page);
});

test('Entries', async ({ page }) => {
  await page.goto(`${baseUrl}/entries`);
  await injectAxe(page);

  /* Wait for entries table to load */
  await expect(page.locator('.main .centeralign')).toHaveClass(/hidden/);
  await checkA11y(page);
});