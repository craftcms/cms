const {exec} = require('child_process');
const path = require('path');
const {chromium, expect} = require('@playwright/test');
const craft = require('./../../_craft');
const events = require('./../../_events');

module.exports = async (config) => {
  process.stdout.write('Running Global setup…');
  process.stdout.write('\n');

  const {baseURL, db, password, projectPath, storageState, testDir, username} =
    config.projects[0].use;

  const browser = await chromium.launch({slowMo: 1000});
  const context = await browser.newContext();
  const page = await context.newPage();

  await page.goto(new URL('./login', baseURL).href);
  await page.fill('.login-username', username);
  await page.fill('.login-password', password);

  await page.click('.login-form button[type="submit"]')
  await page.waitForURL('**/admin/dashboard');


  const title = page.locator('h1');
  await expect(title).toHaveText('Dashboard');

  // Save signed-in state
  await page.context().storageState({path: storageState});
  await browser.close();

  // Backup Craft database with saved session
  await craft.dbBackup();

  process.stdout.write('Calling after global setup event…');
  process.stdout.write('\n');

  events.globalSetup.emit('after');
};
