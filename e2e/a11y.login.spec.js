const { test, expect } = require('@playwright/test');
const { injectAxe, checkA11y, getAxeResults, getViolations } = require('axe-playwright');
const { createHtmlReport } = require("axe-html-reporter");
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

  const axeResults = await getAxeResults(page);
  createHtmlReport({
    results: axeResults,
    options: {
      outputDir: "report/axe",
      reportFileName: `axe.html`,
    },
  });

  const finalViolations = violations.filter(violation => violation.tags.indexOf('best-practice') < 0 );
  expect(finalViolations.length).toEqual(0);
});