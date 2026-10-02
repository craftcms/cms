/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');
test.describe.configure({mode: 'serial'});

test.describe('Plugin Actions', () => {
  test.afterAll(async ({craftSetup}) => {
    await craftSetup.cleanAll();
  });

  test('Install the plugin', async ({page, baseURL}) => {
    await page.goto('./plugin-store/seomatic');

    // Wait plugin request to be done
    await page.waitForResponse(
      (response) =>
        response
          .url()
          .includes('//api.craftcms.com/v1/plugin-store/plugin/seomatic'),
      {timeout: 30 * 1000}
    );

    // Developer name
    const title = page.locator('.plugin-details-header h1');
    await expect(title).toContainText('SEOmatic');

    // Try the plugin
    await page.click('.plugin-editions-edition button:has-text("Try")');

    // Go through the install process
    const status = page.locator('#status');
    await expect(status).toContainText('Checking environment');
    await page.waitForResponse(
      (response) => response.url().includes('precheck'),
      {timeout: 30 * 1000}
    ); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Finstall%2Fprecheck
    await expect(status).toContainText(
      'Updating Composer dependencies (this may take a minute)…'
    );
    await page.waitForResponse(
      (response) => response.url().includes('composer-install'),
      {timeout: 30 * 1000}
    ); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Finstall%2Fcomposer-install
    await expect(status).toContainText('Installing the plugin');
    await page.waitForResponse(
      (response) => response.url().includes('craft-install'),
      {timeout: 30 * 1000}
    ); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Finstall%2Fcraft-install
    await expect(status).toContainText('All done!');
  });

  test('Uninstall and remove the plugin', async ({page, baseURL}) => {
    page.goto('./settings/plugins');

    // Uninstall the plugin
    const menuBtnSelector = 'tr[data-handle="seomatic"] button.menubtn';
    const menuBtn = page.locator(menuBtnSelector);
    await menuBtn.waitFor();

    await page.click(menuBtnSelector);

    let menuId = await menuBtn.getAttribute('aria-controls');
    menuId = menuId.replace('.', '\\.');

    page.on('dialog', (dialog) => dialog.accept());

    const uninstallBtnSelector = '#' + menuId + ' button:has-text("Uninstall")';
    const uninstallBtn = page.locator(uninstallBtnSelector);
    await uninstallBtn.waitFor();
    await page.click(uninstallBtnSelector);

    // Remove the plugin
    const menuBtn2 = page.locator(menuBtnSelector);
    await menuBtn2.waitFor();
    await page.click(menuBtnSelector);

    let menuId2 = await menuBtn2.getAttribute('aria-controls');
    menuId2 = menuId2.replace('.', '\\.');

    const removeBtnSelector = '#' + menuId2 + ' button:has-text("Remove")';
    const removeBtn = page.locator(removeBtnSelector);
    await removeBtn.waitFor();
    await page.click(removeBtnSelector);

    const status = page.locator('#status');
    await expect(status).toContainText('Checking environment');
    await page.waitForResponse(
      (response) => response.url().includes('precheck'),
      {timeout: 30 * 1000}
    ); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Fremove%2Fprecheck
    await expect(status).toContainText(
      'Updating Composer dependencies (this may take a minute)…'
    );
    await page.waitForResponse(
      (response) => response.url().includes('composer-remove'),
      {timeout: 30 * 1000}
    ); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Fremove%2Fcomposer-remove
    await expect(status).toContainText('The plugin was removed successfully.');
    await page.waitForResponse(
      (response) => response.url().includes('finish'),
      {timeout: 30 * 1000}
    ); //craft4.test/index.php?p=admin%2Factions%2Fpluginstore%2Fremove%2Ffinish
  });
});
