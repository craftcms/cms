const { test, expect } = require('@playwright/test');
const { injectAxe, checkA11y, getAxeResults } = require('axe-playwright');
const { createHtmlReport } = require('axe-html-reporter');
const baseUrl = 'https://craft3.nitro/admin';

test('Login accessibility test', async ({ page }) => {
  await page.goto(`${baseUrl}/login`);
  await injectAxe(page);

  const axeResults = await getAxeResults(page);

  createHtmlReport({
    results: axeResults,
    options: {
      outputDir: "report/axe",
      reportFileName: `a11y-results-login.html`,
    },
  });

  await checkA11y(page);
});