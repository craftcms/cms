/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test.describe('Component select input', () => {
  test.beforeAll(async ({browser}) => {
    const page = await browser.newPage();
    for (const name of ['Group A', 'Group B']) {
      await page.goto('./settings/users/groups/new');
      await page.fill('#content input#name', name);
      await page.click('#action-buttons button[type="submit"]');
      await page.waitForURL('**/settings/users');
    }
    await page.close();
  });

  // https://github.com/craftcms/cms/issues/21116
  test('Replacing a chip without a limit swaps it out', async ({page}) => {
    const userGroups = page.locator('#user-groups');
    const chips = userGroups.locator('.chip');
    const save = () =>
      page.click('#action-buttons button[type="submit"]:has-text("Save")');

    await page.goto('./myaccount/permissions');
    await userGroups.locator('.add-btn').click();
    await page.getByRole('button', {name: 'Group A', exact: true}).click();
    await expect(chips).toHaveCount(1);
    await save();
    await page.reload();
    await expect(chips).toHaveText(['Group A']);

    await chips.first().locator('.action-btn').click();
    await page
      .locator('.menu--disclosure:visible')
      .getByRole('button', {name: 'Replace'})
      .click();
    await page.getByRole('button', {name: 'Group B', exact: true}).click();
    await expect(chips).toHaveText(['Group B']);

    await save();
    await page.reload();
    await expect(chips).toHaveText(['Group B']);
  });
});
