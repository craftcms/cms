const {exec} = require('child_process');
const path = require('path');
const {chromium, expect} = require('@playwright/test');

module.exports = async (config) => {
  const {baseURL, db, password, projectPath, storageState, testDir, username} =
    config.projects[0].use;

  // Install Craft
  // await exec(`${projectPath}/craft install/craft`, (error, stdout, stderr) => {
  //   console.log(stdout);
  // });

  // Create admin user
  // await exec(`${projectPath}/craft users/create --admin=1 --email=playwright@craftcms.com --username=${username} --password=${password}`, (error, stdout, stderr) => {
  //   console.log(stdout);
  // });

  // Backup DB for quick restores
  // const dbBackupPath = path.resolve(path.join(testDir, '.backup.sql'));
  // await exec(`${projectPath}/craft db/backup ${dbBackupPath}`, (error, stdout, stderr) => {
  //   console.log(stdout);
  // });

  const browser = await chromium.launch();
  const page = await browser.newPage();

  await page.goto(new URL('./login', baseURL).href);
  await page.fill('#loginName', username);
  await page.fill('#password', password);

  await Promise.all([page.waitForNavigation(), page.click('button#submit')]);

  const title = page.locator('h1');
  await expect(title).toHaveText('Dashboard');

  // Save signed-in state
  await page.context().storageState({path: storageState});
  // await page.close();
  await browser.close();
};
