const {test, expect} = require('@craftcms/playwright');

test.beforeAll(async ({craftSetup}) => {
  await craftSetup.cleanAll();
  await craftSetup.loadFixture('Entry');
});

test.beforeEach(async ({page}) => {
  await page.goto('./entries/testMatrix');
});

test.describe('Element index', () => {
  const titleText = 'Entry with element index matrix';
  const originalText = 'card 1';
  const editedText = originalText + ' edited';
  const titleFieldLocator = '#title';
  const matrixElementIndexContainerLocator = '#fields-matrixElementIndexField';
  const matrixElementIndexFieldLocator =
    matrixElementIndexContainerLocator + '-field';
  const firstCardLocator =
    matrixElementIndexFieldLocator + ' .card-grid > li:first-child .card';
  const slideoutLocator = '.slideout-container:not(.hidden)';
  const textFieldLocator =
    '.so-content input[name$="[fields][plainTextField6]"]';

  // create new entry that contains matrix field in element index view mode
  // add nested entry to the matrix & save, check the card was added and has the modified indicator
  // check that the nested entry can be edited after being created
  // and save the root entry
  test('Create new root entry with element index matrix field', async ({
    page,
    baseURL,
    craftEntry,
  }) => {
    const slideout = page.locator(slideoutLocator);

    // create new entry that contains matrix field in cards view mode
    await page.getByLabel('New entry in the Test Matrix').click();
    await page.waitForLoadState();

    // switch to the "With Matrix in Element Index mode" entry type
    await page.locator('#entryType-button').click();
    await page
      .getByRole('button', {name: 'With Matrix in Element Index'})
      .click();

    // wait for the autosave to complete
    await craftEntry.waitForAutosaveToComplete(page);
    await page
      .locator(matrixElementIndexContainerLocator)
      .waitFor({state: 'visible'});

    // set the title
    await page
      .locator(titleFieldLocator)
      .pressSequentially(titleText, {delay: 50});
    await craftEntry.waitForAutosaveToComplete(page);

    // add nested entry to the matrix
    await page
      .locator('#content')
      .getByRole('button', {name: 'New entry'})
      .click();
    await craftEntry.waitForAutosaveToComplete(page);

    // fill out the field
    await slideout
      .locator(textFieldLocator)
      .pressSequentially(originalText, {delay: 100});
    //await craftEntry.waitForAutosaveToComplete(page);

    // check if the draft card was attached to the dom
    const firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor({state: 'attached', timeout: 30000});
    const firstCardId = await firstCard.getAttribute('id');

    // save nested entry
    await slideout.getByRole('button', {name: 'Create entry'}).click();
    await craftEntry.waitForAutosaveToComplete(page);

    // wait for the status of the card to get updated
    await craftEntry.waitForAutosaveToComplete(page);
    await firstCard.locator('.status-label-text:text-is("Live")').waitFor();

    // check the card was added and the field has the modified indicator
    await expect(page.locator('#' + firstCardId)).toContainText(originalText);
    await expect(
      page
        .locator(matrixElementIndexFieldLocator)
        .getByTitle(craftEntry.fieldModifiedText)
    ).toBeVisible();

    // check that the nested entry can be edited after being created
    await page
      .locator('#' + firstCardId)
      .getByRole('button', {name: 'Edit entry'})
      .click();
    await expect(slideout.locator(textFieldLocator)).toHaveValue(originalText);
    await page.getByRole('button', {name: 'Cancel'}).click();

    // and save the root entry
    await page.getByRole('button', {name: 'Create entry'}).click();
  });

  // edit entry from previous test
  // check that the entry nested in a matrix can be edited
  // update text field value & save
  // check that the modified indicators show
  // save root entry and check if the change is there
  test('Edit nested entry, save and check if the value saved', async ({
    page,
    baseURL,
    craftEntry,
  }) => {
    const slideout = page.locator(slideoutLocator);

    // edit entry from previous test
    await craftEntry.editFirstEntryInElementIndexTable(page);

    let firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor();
    let firstCardId = await firstCard.getAttribute('id');

    // check that the entry nested in a matrix can be edited
    await page
      .locator('#' + firstCardId)
      .getByRole('button', {name: 'Edit entry'})
      .click();

    // update text field value & save
    await slideout.locator(textFieldLocator).waitFor();
    await slideout.locator(textFieldLocator).clear();
    await slideout
      .locator(textFieldLocator)
      .pressSequentially(editedText, {delay: 100});

    // if we close the slideout, check that the card has the Edited status pill
    await slideout.locator('.so-footer .discard-changes-btn').waitFor();
    await slideout.getByRole('button', {name: 'Close'}).click();
    await expect(
      firstCard.locator(
        '.card-body ul:last-child li:last-child .status-label-text'
      )
    ).toContainText('Edited');

    // open the slideout again, and save
    await page
      .locator('#' + firstCardId)
      .getByRole('button', {name: 'Edit entry'})
      .click();
    await slideout.getByRole('button', {name: 'Save'}).click();

    await craftEntry.waitForAutosaveToComplete(page);

    // check that the field modified indicator doesn't show and there's only one status pill
    await expect(
      page
        .locator(matrixElementIndexFieldLocator)
        .getByTitle(craftEntry.fieldModifiedText)
    ).not.toBeVisible();
    await expect(firstCard.locator('.status-label')).toHaveCount(1);

    // save root entry
    await page.keyboard.press('ControlOrMeta+s');
    await craftEntry.waitForAutosaveToComplete(page);
    //await firstCard.waitFor();
    firstCardId = await firstCard.getAttribute('id');

    // and check if the change is there
    await expect(page.locator('#' + firstCardId)).toContainText(editedText);
  });

  // edit entry from previous test
  // add a second nested entry to the matrix & save
  // check the card was added and that the blue indicators are there
  // discard root entry changes
  // check that there's no blue indicators and there's only one card in the matrix field
  test('Check that added nested entry can be discarded', async ({
    page,
    baseURL,
    craftEntry,
  }) => {
    const slideout = page.locator(slideoutLocator);

    // edit entry from previous test
    await craftEntry.editFirstEntryInElementIndexTable(page);

    // we need to turn the root entry into a draft or the second nested entry won't save against a draft via playwright in headless mode
    await page.locator('#slug').pressSequentially('test', {delay: 100});
    await page.locator('#content-notice .discard-changes-btn').waitFor();

    // add a second nested entry to the matrix,
    await page.getByRole('button', {name: 'New entry'}).click();
    await slideout.locator(textFieldLocator).waitFor();
    await slideout
      .locator(textFieldLocator)
      .pressSequentially('card 2', {delay: 100});

    // wait for the draft card to be attached
    const lastCard = page.locator(
      matrixElementIndexContainerLocator + ' .card-grid > li:last-child .card'
    );
    await lastCard.waitFor({state: 'attached'});

    // save the second nested entry
    await slideout.getByRole('button', {name: 'Create entry'}).click();

    // wait till save is done
    await craftEntry.waitForAutosaveToComplete(page);
    await lastCard.locator('.status-label-text:text-is("Live")').waitFor();

    // check the card was added and that the blue indicators are there
    await expect(lastCard).toContainText('card 2');
    await expect(
      page
        .locator(matrixElementIndexFieldLocator)
        .getByTitle(craftEntry.fieldModifiedText)
    ).toBeVisible();
    await expect(lastCard.getByTitle(craftEntry.newEntryText)).toBeVisible();

    // discard root entry changes
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });
    await page.locator('#content .discard-changes-btn').click();
    await page.waitForLoadState();

    // check that there's no blue indicators
    await expect(
      page
        .locator(matrixElementIndexFieldLocator)
        .getByTitle(craftEntry.fieldModifiedText)
    ).not.toBeVisible();

    // and there's only one card in the matrix field
    await expect(
      page.locator(matrixElementIndexContainerLocator + ' .card-grid .card')
    ).toHaveCount(1);
  });
});
