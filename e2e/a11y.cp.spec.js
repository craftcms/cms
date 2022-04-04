const { test, expect } = require('@playwright/test');
const {injectAxe, checkA11y, getViolations, getAxeResults} = require('axe-playwright');
const { createHtmlReport } = require("axe-html-reporter");

const baseUrl = 'https://craft3.nitro/admin';

const testOptions = {
  axeOptions: {
    runOnly: {
      type: 'tag',
      values: ['wcag2a', 'wcag2aa'],
    },
  },
};

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

  const violations = await getViolations(page, null, testOptions);
  expect(violations.length).toEqual(0);
});

test('Entries', async ({ page }) => {
  await page.goto(`${baseUrl}/entries`);
  await injectAxe(page);

  /* Wait for entries table to load */
  await expect(page.locator('.main .centeralign')).toHaveClass(/hidden/);
  const violations = await getViolations(page, null, testOptions);
  const axeResults = await getAxeResults(page);
  createHtmlReport({
    results: axeResults,
    options: {
      outputDir: "report/axe",
      reportFileName: `cp-results.html`,
    },
  });
  expect(violations.length).toEqual(0);
});