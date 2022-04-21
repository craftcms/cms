const {test, expect} = require('@playwright/test');
test.describe.configure({mode: 'serial'});

test('Install the plugin', async ({page, baseURL}) => {
  await page.goto(baseURL + '/plugin-store/seomatic');

  // Wait plugin request to be done
  await page.waitForResponse((response) =>
    response
      .url()
      .includes('//api.craftcms.com/v1/plugin-store/plugin/seomatic')
  );

  // Developer name
  const title = page.locator('.plugin-details-header h1');
  await expect(title).toContainText('SEOmatic');

  // Try the plugin
  await page.click('.plugin-editions-edition button:has-text("Try")');

  // Go through the install process
  const status = page.locator('#status');
  await expect(status).toContainText('Checking environment');
  await page.waitForResponse((response) => response.url().includes('precheck')); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Finstall%2Fprecheck
  await expect(status).toContainText(
    'Updating Composer dependencies (this may take a minute)…'
  );
  await page.waitForResponse((response) =>
    response.url().includes('composer-install')
  ); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Finstall%2Fcomposer-install
  await expect(status).toContainText('Installing the plugin');
  await page.waitForResponse((response) =>
    response.url().includes('craft-install')
  ); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Finstall%2Fcraft-install
  await expect(status).toContainText('All done!');
});

test('Uninstall and remove the plugin', async ({page, baseURL}) => {
  page.goto(baseURL + '/settings/plugins');

  // Uninstall the plugin
  const menuBtnSelector = 'tr[data-handle="seomatic"] button.menubtn';
  const menuBtn = await page.waitForSelector(menuBtnSelector);

  await page.click(menuBtnSelector);

  let menuId = await menuBtn.getAttribute('aria-controls');
  menuId = menuId.replace('.', '\\.');

  page.on('dialog', (dialog) => dialog.accept());

  const uninstallBtnSelector = '#' + menuId + ' a:has-text("Uninstall")';
  await page.waitForSelector(uninstallBtnSelector);
  await page.click(uninstallBtnSelector);

  // Remove the plugin
  const menuBtn2 = await page.waitForSelector(menuBtnSelector);
  await page.click(menuBtnSelector);

  let menuId2 = await menuBtn2.getAttribute('aria-controls');
  menuId2 = menuId2.replace('.', '\\.');

  const removeBtnSelector = '#' + menuId2 + ' a:has-text("Remove")';
  await page.waitForSelector(removeBtnSelector);
  await page.click(removeBtnSelector);

  const status = page.locator('#status');
  await expect(status).toContainText('Checking environment');
  await page.waitForResponse((response) => response.url().includes('precheck')); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Fremove%2Fprecheck
  await expect(status).toContainText(
    'Updating Composer dependencies (this may take a minute)…'
  );
  await page.waitForResponse((response) =>
    response.url().includes('composer-remove')
  ); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Fremove%2Fcomposer-remove
  await expect(status).toContainText('The plugin was removed successfully.');
  await page.waitForResponse((response) => response.url().includes('finish')); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Fremove%2Ffinish
});
