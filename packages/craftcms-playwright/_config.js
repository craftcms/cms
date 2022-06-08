/* jshint esversion: 9, strict: false */
const path = require('path');
const {devices} = require('@playwright/test');
const testDir = './tests-playwright';
require('dotenv').config({path: path.resolve(path.join(testDir, '.env'))});

let cpTrigger = process.env.PLAYWRIGHT_CRAFT_CP_TRIGGER ?? 'admin';
cpTrigger = `./${cpTrigger}/`;
const storageStateFilename = '.authentication.json';
let baseURL = process.env.PLAYWRIGHT_SITE ?? 'http://127.0.0.1:8089/';
baseURL = new URL(cpTrigger, baseURL).href;
const username = process.env.PW_AUTH_USERNAME ?? 'admin';
const password = process.env.PW_AUTH_PASSWORD ?? 'NewPassword';

module.exports = {
  globalSetup: require.resolve(path.join(__dirname, './_global-setup.js')),
  globalTeardown: require.resolve(
    path.join(__dirname, './_global-teardown.js')
  ),
  testDir: testDir,
  use: {
    baseURL,
    username,
    password,
    testDir,
    ignoreHTTPSErrors: true,
    storageState: path.join(testDir, storageStateFilename),
  },
  timeout: 120 * 1000,
  expect: {
    timeout: 5000,
  },
  projects: [
    {
      name: 'chromium',
      use: {...devices['Desktop Chrome']},
    },
  ],
  workers: 1,
};
