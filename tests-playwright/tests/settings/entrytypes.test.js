/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

let entryTypeName = 'My New Entry Type';
let entryTypeHandle = 'myNewEntryType';

test.describe('Entry Types - Empty Page', () => {
  // Make sure we start each test on the entry types page
  test.beforeEach(async ({page}) => {
    await page.goto('./settings/entry-types');
  });

  // Check if the entry types page shows empty message
  test('Check empty Entry Types page', async ({page, baseURL}) => {
    const zilch = page.locator('#content .zilch:visible p');
    await expect(zilch).toHaveText('No entry types exist yet.');
  });

  // Check new entry type page loads
  test('New entry type page', async ({page, baseURL}) => {
    const newButtonSelector = '#header #action-buttons a.submit';
    const newSectionButton = page.locator(newButtonSelector);
    await expect(newSectionButton).toHaveText('New entry type');
    await page.click(newButtonSelector);
    await expect(page).toHaveURL(
      new URL('./settings/entry-types/new', baseURL).href
    );

    const fields = [
      page.locator('#content input#name'),
      page.locator('#content input#handle'),
    ];

    for (let i = 0; i < fields.length; i++) {
      await expect(fields[i]).toBeEditable();
    }
  });
});

test.describe('Entry Type - New', () => {
  // Check if we can add a new entry type
  test('Create new entry type', async ({page, baseURL}) => {
    await page.goto('./settings/entry-types/new');

    await page.fill('#content input#name', entryTypeName);
    await expect(page.locator('#content input#handle')).toHaveValue(
      entryTypeHandle
    );

    await page.click('#action-buttons button.menubtn');
    await page.click(
      '#form-action-menu a:has-text("Save and continue editing")'
    );

    const urlRegExp = new RegExp(/settings\/entry-types\/\d+?$/, 'i');
    await expect(page).toHaveURL(urlRegExp);
    await expect(page.locator('h1')).toHaveText(entryTypeName);
  });
});

test.describe('Entry Type - Page', () => {
  // Make sure we start each test on the fields page
  test.beforeEach(async ({page}) => {
    await page.goto('./settings/entry-types');
  });

  // Check if we can see the newly created field on the field page
  test('Check entry types page with content', async ({page, baseURL}) => {
    let fieldsTable = page.locator(
      '#content #entrytypes-vue-admin-table .vuetable'
    );
    await expect(fieldsTable).toBeVisible();
    await expect(fieldsTable).toContainText(entryTypeName);
    await expect(fieldsTable).toContainText(entryTypeHandle);
  });

  // Check searching through fields
  test('Check searching', async ({page, baseURL}) => {
    let searchInput = page.locator(
      '#content #entrytypes-vue-admin-table .search input[placeholder="Search"]'
    );
    await expect(searchInput).toBeVisible();

    await searchInput.fill('I dont exist');
    await expect(
      page.locator('#content #entrytypes-vue-admin-table .zilch:visible p')
    ).toHaveText('No results.');

    await searchInput.fill(entryTypeName);
    await expect(
      page.locator('#content #entrytypes-vue-admin-table .vuetable')
    ).toContainText(entryTypeHandle);
  });
});

test.describe('Entry Type - Edit', () => {
  // Check if we can add a new entry type
  test('Edit existing entry type', async ({page, baseURL}) => {
    await page.goto('./settings/entry-types');

    await page.click(
      '#content #entrytypes-vue-admin-table a:has-text("' + entryTypeName + '")'
    );
    let urlRegExp = new RegExp(/settings\/entry-types\/\d+?$/, 'i');
    await expect(page).toHaveURL(urlRegExp);

    await page.fill('#content input#name', entryTypeName + ' Updated');
    await expect(page.locator('#content input#handle')).toHaveValue(
      entryTypeHandle
    );

    await page.click('#action-buttons button:has-text("Save")');

    urlRegExp = new RegExp(/settings\/entry-types$/, 'i');
    await expect(page).toHaveURL(urlRegExp);

    let fieldsTable = page.locator(
      '#content #entrytypes-vue-admin-table .vuetable'
    );
    await expect(fieldsTable).toBeVisible();
    await expect(fieldsTable).toContainText(entryTypeName + ' Updated');
    await expect(fieldsTable).toContainText(entryTypeHandle);
  });
});
