// global-setup.js
const {chromium, expect} = require('@playwright/test');

module.exports = async (config) => {
  const {baseURL} = config.projects[0].use;
  const browser = await chromium.launch({
    // devtools: true
  });
  const page = await browser.newPage();

  await page.goto(baseURL);
  await page.fill('#loginName', 'admin');
  await page.fill('#password', 'password');
  await page.click('button#submit');

  const title = page.locator('h1');
  await expect(title).toHaveText('Dashboard');

  // Save signed-in state
  await page
    .context()
    .storageState({path: './tests/.playwright/authentication/admin.json'});
  await browser.close();
};
