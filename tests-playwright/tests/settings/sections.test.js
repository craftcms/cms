/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

let sectionName = 'My New Channel';
let sectionHandle = 'myNewChannel';

test.describe('Sections - Page', () => {
  // Make sure we start each test on the sections page
  test.beforeEach(async ({page}) => {
    await page.goto('./settings/sections');
  });

  // Check if the sections page shows empty sections message
  test('Check empty sections page', async ({page, baseURL}) => {
    const zilch = page.locator('#content .zilch:visible p');
    await expect(zilch).toHaveText('No sections exist yet.');
  });

  // Check if new section page loads
  test('New section page', async ({page, baseURL}) => {
    const newButtonSelector = '#header #action-buttons a.submit';
    const newSectionButton = page.locator(newButtonSelector);
    await expect(newSectionButton).toHaveText('New section');
    await page.click(newButtonSelector);
    await expect(page).toHaveURL(
      new URL('./settings/sections/new', baseURL).href
    );

    const fields = [
      page.locator('#content input#name'),
      page.locator('#content input#handle'),
      page.locator('#content button#enableVersioning'),
      page.locator('#content select#type'),
    ];

    for (let i = 0; i < fields.length; i++) {
      await expect(fields[i]).toBeEditable();
    }
  });
});

test.describe('Sections - New', () => {
  // Check if we can add a new channel section and create entry type for it on the fly
  test('Create new channel', async ({page, baseURL}) => {
    await page.goto('./settings/sections/new');

    await page.fill('#content input#name', sectionName);
    await expect(page.locator('#content input#handle')).toHaveValue(
      sectionHandle
    );
    await expect(page.locator('#content select#type')).toHaveValue('channel');
    await expect(page.locator('#content #entry-types .components')).toBeEmpty();

    await page.click('#content #entry-types .create-btn');

    await expect(page.locator('.cp-screen.slideout')).toBeVisible();

    let slideoutId = await page
      .locator('.cp-screen.slideout')
      .getAttribute('id');
    let slideout = page.locator('#' + slideoutId);

    await slideout.getByLabel('Name').fill('Channel Entry Type');
    await expect(slideout.getByLabel('Handle')).toHaveValue('channelEntryType');

    await page.click('#' + slideoutId + ' .so-footer .btn.submit');

    await expect(
      page.locator('#content #entry-types .components')
    ).toContainText('Channel Entry Type');

    await page.click('#action-buttons button.menubtn');
    await page.click(
      '#form-action-menu a:has-text("Save and continue editing")'
    );

    const urlRegExp = new RegExp(/settings\/sections\/\d+?$/, 'i');
    await expect(page).toHaveURL(urlRegExp);
    await expect(page.locator('h1')).toHaveText(sectionName);
  });

  // Check if we can see the newly created section on the sections page
  test('Check sections page with content', async ({page, baseURL}) => {
    await page.goto('./settings/sections');

    let sectionsTable = page.locator(
      '#content #sections-vue-admin-table .vuetable'
    );
    await expect(sectionsTable).toBeVisible();
    await expect(sectionsTable).toContainText(sectionName);
    await expect(sectionsTable).toContainText(sectionHandle);
  });
});
