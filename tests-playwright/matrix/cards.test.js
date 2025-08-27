const {test, expect} = require('@craftcms/playwright');
const entries = require('@craftcms/playwright/playwright/helpers/entries');

test.beforeAll(async ({craftSetup}) => {
  await craftSetup.cleanAll();
  await craftSetup.loadFixture('Entry');
});

test.beforeEach(async ({page}) => {
  await page.goto('./entries/testMatrix');
});

test.describe('Cards', () => {
  const titleText = 'Entry with cards matrix';
  const originalText = 'card 1';
  const editedText = originalText + ' edited';
  const titleFieldLocator = '#title';
  const matrixCardsFieldLocator = '#fields-matrixCardsField-field';
  const firstCardLocator =
    matrixCardsFieldLocator + ' .cards > li:first-child .card';
  const slideoutLocator = '.slideout-container:not(.hidden)';
  const textFieldLocator =
    '.so-content input[name$="[fields][plainTextField6]"]';

  // create new entry that contains matrix field in cards view mode
  // add nested entry to the matrix & save
  // check the card was added and has the modified indicator
  // check that the nested entry can be edited after being created
  // save the root entry
  test('Create new root entry with card matrix field', async ({
    page,
    baseURL,
  }) => {
    const slideout = page.locator(slideoutLocator);

    // create new entry that contains matrix field in cards view mode
    await page.getByLabel('New entry in the Test Matrix').click();
    await page.waitForLoadState();

    // set the title
    await page
      .locator(titleFieldLocator)
      .pressSequentially(titleText, {delay: 50});
    await entries.waitForAutosaveToComplete(page);

    // add nested entry to the matrix,
    await page
      .locator('#content')
      .getByRole('button', {name: 'New entry'})
      .click();

    // fill out the field
    await slideout
      .locator(textFieldLocator)
      .pressSequentially(originalText, {delay: 100});

    // check if the draft card was attached to the dom
    const firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor({state: 'attached'});
    const firstCardId = await firstCard.getAttribute('id');

    // save nested entry
    await page.getByRole('button', {name: 'Create entry'}).click();

    // wait for the status of the card to get updated
    await firstCard.locator('.status-label-text:text-is("Live")').waitFor();

    // check the card was added, has the right text, and the matrix field has the modified indicator
    await expect(page.locator('#' + firstCardId)).toContainText(originalText);
    await expect(
      page
        .locator(matrixCardsFieldLocator)
        .getByTitle(entries.fieldModifiedText)
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
  // check that the modified indicators show (for the matrix field and the card)
  // save root entry and check if the change is there
  test('Edit nested entry, save and check if the value saved', async ({
    page,
    baseURL,
  }) => {
    const slideout = page.locator(slideoutLocator);

    // edit entry from previous test
    await entries.editFirstEntryInElementIndexTable(page);

    // get the first card
    let firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor();
    let firstCardId = await firstCard.getAttribute('id');

    // check that the entry nested in a matrix can be edited
    await page
      .locator('#' + firstCardId)
      .getByRole('button', {name: 'Edit entry'})
      .click();

    // update text field value in the nested entry
    await slideout.locator(textFieldLocator).waitFor();
    await slideout.locator(textFieldLocator).clear();
    await slideout
      .locator(textFieldLocator)
      .pressSequentially(editedText, {delay: 100});
    await slideout.getByRole('button', {name: 'Save'}).click();
    await entries.waitForAutosaveToComplete(page);

    // check that both modified indicators show
    await expect(
      page
        .locator(matrixCardsFieldLocator)
        .getByTitle(entries.fieldModifiedText)
    ).toBeVisible();
    await expect(firstCard.getByTitle(entries.editedEntryText)).toBeVisible();

    // save root entry
    await page.keyboard.press('ControlOrMeta+s');
    await entries.waitForAutosaveToComplete(page);
    firstCardId = await firstCard.getAttribute('id');

    // and check if the change is there
    await expect(page.locator('#' + firstCardId)).toContainText(editedText);
  });

  // edit entry from previous test
  // add a second nested entry to the matrix & save
  // check the card was added and that the blue indicators are there (for the matrix field and the card)
  // discard root entry changes
  // check that there's no blue indicator for the matrix field and that there's only one card in the matrix field
  test('Check that added nested entry can be discarded', async ({
    page,
    baseURL,
  }) => {
    const slideout = page.locator(slideoutLocator);
    const matrixCardsField = page.locator(matrixCardsFieldLocator);

    // edit entry from previous test
    await entries.editFirstEntryInElementIndexTable(page);

    // we need to turn the root entry into a draft or the second nested entry won't save against a draft via playwright in headless mode
    await page.locator('#slug').pressSequentially('test', {delay: 100});
    await page.locator('#content-notice .discard-changes-btn').waitFor();

    // add a second nested entry to the matrix
    await page.getByRole('button', {name: 'New entry'}).click();
    await slideout.locator(textFieldLocator).waitFor();
    await slideout
      .locator(textFieldLocator)
      .pressSequentially('card 2', {delay: 100});

    // wait for the draft card to be attached
    const lastCard = matrixCardsField.locator('.cards > li:last-child .card');
    await lastCard.waitFor({state: 'attached'});

    // save the second nested entry
    await slideout.getByRole('button', {name: 'Create entry'}).click();

    // wait till save is done
    await entries.waitForAutosaveToComplete(page);
    await lastCard.locator('.status-label-text:text-is("Live")').waitFor();

    // check the card was added and that the blue indicators are there
    await expect(lastCard).toContainText('card 2');
    await expect(
      matrixCardsField.getByTitle(entries.fieldModifiedText)
    ).toBeVisible();
    await expect(
      matrixCardsField.getByTitle(entries.newEntryText)
    ).toBeVisible();

    // discard root entry changes
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });
    await page.locator('#content .discard-changes-btn').click();
    await page.waitForLoadState();

    // check that there's no blue indicators
    await expect(
      matrixCardsField.getByTitle(entries.fieldModifiedText)
    ).not.toBeVisible();

    // and there's only one card in the matrix field
    await expect(matrixCardsField.locator('.cards .card')).toHaveCount(1);
  });
});
