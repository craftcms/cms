const { test, expect } = require('@playwright/test');
const { injectAxe, checkA11y } = require('axe-playwright');
const baseUrl = 'https://craft3.nitro/admin';

test('Login accessibility test', async ({ page }) => {
  await page.goto(`${baseUrl}/login`);
  await injectAxe(page);

  await checkA11y(page);
});