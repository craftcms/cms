/* jshint esversion: 9, strict: false */
const path = require('path');
const {devices} = require('@playwright/test');
require('dotenv').config({path: path.resolve('./tests/.env')});

const cpTrigger = './admin/';
const testDir = './tests/playwright';
const storageStateFilename = '.authentication.json';

module.exports = {
  globalSetup: require.resolve(path.join(__dirname, './_global-setup.js')),
  globalTeardown: require.resolve(
    path.join(__dirname, './_global-teardown.js')
  ),
  testDir: testDir,
  use: {
    baseURL: new URL(cpTrigger, process.env.PW_TEST_DOMAIN).href,
    username: process.env.PW_AUTHENTICATION_USERNAME ?? 'admin',
    password: process.env.PW_AUTHENTICATION_PASSWORD ?? 'NewPassword',
    projectPath: process.env.PW_PROJECT_PATH,
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
