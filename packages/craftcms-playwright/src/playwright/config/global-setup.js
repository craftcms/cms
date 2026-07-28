const {chromium, expect} = require('@playwright/test');
const events = require('./../events');
const {Setup} = require('./../fixtures/setup');
const logger = require('../logger');

module.exports = async (config) => {
  logger.await('Running global setup...');

  const {baseURL, password, storageState, username} = config.projects[0].use;

  const browser = await chromium.launch({slowMo: 1000});
  const context = await browser.newContext();
  const page = await context.newPage();

  await page.goto(new URL('./login', baseURL).href);

  // The login form is the `craft-login-form` web component; its inputs are
  // nested inside `craft-input`/`craft-input-password`, which also carry the
  // `name` attribute themselves — target the native `<input>`s specifically
  // (Playwright's CSS engine pierces the shadow DOM to reach them).
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);

  await page.click('[type="submit"]');
  await page.waitForURL('**/admin/dashboard');

  const title = page.locator('h1');
  await expect(title).toHaveText('Dashboard');

  // Save signed-in state
  await page.context().storageState({path: storageState});
  await browser.close();

  // Backup Craft database with saved session
  const setup = new Setup();
  await setup.dbBackup();

  await events.globalSetup.emit('after');

  logger.success('Global setup completed.');
};
