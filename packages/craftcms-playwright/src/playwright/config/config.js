const path = require('path');
const {devices} = require('@playwright/test');

const testDir = './tests-playwright';
require('dotenv').config({path: path.resolve(path.join(testDir, '.env'))});

const cpTrigger = `./${process.env.CRAFT_CP_TRIGGER ?? 'admin'}/`;
const storageStateFilename = '.authentication.json';
const siteUrl = process.env.PRIMARY_SITE_URL ?? 'https://playwright.ddev.site/';
const cpUrl = new URL(cpTrigger, siteUrl).href;
const username = process.env.AUTH_USERNAME ?? 'admin';
const password = process.env.AUTH_PASSWORD ?? 'NewPassword';
const fixturesNamespace = process.env.CODECEPTION_FIXTURES_NAMESPACE;

module.exports = {
  globalSetup: require.resolve('./global-setup.js'),
  globalTeardown: require.resolve('./global-teardown.js'),
  testDir,
  fixturesNamespace,
  use: {
    baseURL: cpUrl,
    username,
    password,
    testDir,
    ignoreHTTPSErrors: true,
    storageState: path.join(testDir, storageStateFilename),
    actionTimeout: 10 * 1000,
  },
  timeout: 30 * 1000,
  expect: {
    timeout: 10 * 1000,
  },
  projects: [
    {
      name: 'chromium',
      use: {...devices['Desktop Chrome']},
    },
  ],
  workers: 1,
};
