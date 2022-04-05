const { test, expect } = require('@playwright/test');
const {injectAxe, checkA11y, getAxeResults} = require('axe-playwright');
const {createHtmlReport} = require('axe-html-reporter');

const baseUrl = 'https://craft3.nitro/admin';

const createReport = (axeResults, pageName) => {
  createHtmlReport({
    results: axeResults,
    options: {
      outputDir: "report/axe",
      reportFileName: `a11y-results-${pageName}.html`,
    },
  });
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
  await expect(page.locator('h1')).toHaveText('Dashboard');
  await injectAxe(page);

  const axeResults = await getAxeResults(page);
  createReport(axeResults, 'dashboard');
  await checkA11y(page);
});

test('Entries', async ({ page }) => {
  await page.goto(`${baseUrl}/entries`);
  await injectAxe(page);

  /* Wait for entries table to load */
  await expect(page.locator('.main .centeralign')).toHaveClass(/hidden/);
  const axeResults = await getAxeResults(page);
  createReport(axeResults, 'entries');
  await checkA11y(page);
});