const { test, expect } = require('@playwright/test');
const {injectAxe, checkA11y, getViolations} = require('axe-playwright');
const baseUrl = 'https://craft3.nitro/admin';

test('Login accessibility test', async ({ page }) => {
  await page.goto(`${baseUrl}/login`);
  await injectAxe(page);

  const violations = await getViolations(page, null, {
    axeOptions: {
      runOnly: {
        type: 'tag',
        values: ['wcag2a', 'wcag2aa'],
      },
    },
  });
  expect(violations.length).toEqual(0);
});