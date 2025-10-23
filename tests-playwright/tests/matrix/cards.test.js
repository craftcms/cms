/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test.beforeAll(async ({craftSetup}) => {
  await craftSetup.cleanAll();
  await craftSetup.loadFixture('Entry');
});

test.beforeEach(async ({page}) => {
  await page.goto('./entries/testMatrix');
});

const titleFieldLocator = '#title';
const slideoutLocator = '.slideout-container:not(.hidden)';
const textFieldLocator = '.so-content input[name$="[fields][plainTextField6]"]';
const firstChildLocator = ' .cards > li:first-child .card';
const lastChildLocator = ' .cards > li:last-child .card';

test.describe('Create, edit, discard', () => {
  const titleText = 'Entry with cards matrix';
  const originalText = 'card 1';
  const editedText = originalText + ' edited';
  const matrixFieldLocator = '#fields-matrixCardsField-field';
  const firstCardLocator = matrixFieldLocator + firstChildLocator;

  test('Create new root entry with card matrix field', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    const slideout = page.locator(slideoutLocator);

    // create new entry that contains matrix field in cards view mode
    await craftEntry.createEntryBySectionName(page, 'Test Matrix');

    // fill the title
    await craftEntry.fillField(page, page, titleFieldLocator, titleText, 50);

    // add nested entry to the matrix field
    await craftMatrix.getNewNestedEntryBtn(page).click();

    // fill out the plain text field
    await craftEntry.fillField(page, slideout, textFieldLocator, originalText);

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
      page.locator(matrixFieldLocator).getByTitle(craftEntry.fieldModifiedText)
    ).toBeVisible();

    // check that the nested entry can be edited after being created and has the right text
    await craftMatrix.getEditNestedEntryBtn(page, firstCardId).click();
    await expect(slideout.locator(textFieldLocator)).toHaveValue(originalText);
    await page.getByRole('button', {name: 'Cancel'}).click();

    // and save the root entry
    await craftEntry.saveRootEntry(page);
  });

  test('Edit nested entry, save and check if the value saved', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    const slideout = page.locator(slideoutLocator);

    // edit entry from previous test
    await craftEntry.editFirstEntryInElementIndexTable(page);

    // get the first card
    let firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor();
    let firstCardId = await firstCard.getAttribute('id');

    // check that the entry nested in a matrix can be edited
    await craftMatrix.getEditNestedEntryBtn(page, firstCardId).click();

    // update the text field value in the nested entry
    await slideout.locator(textFieldLocator).waitFor();
    await slideout.locator(textFieldLocator).clear();
    await craftEntry.fillField(page, slideout, textFieldLocator, editedText);

    // save the nested entry
    await slideout.getByRole('button', {name: 'Save'}).click();
    await craftEntry.waitForAutosaveToComplete(page);

    // check that both modified indicators show
    await expect(
      page.locator(matrixFieldLocator).getByTitle(craftEntry.fieldModifiedText)
    ).toBeVisible();
    await expect(
      firstCard.getByTitle(craftEntry.editedEntryText)
    ).toBeVisible();

    // save root entry
    await page.keyboard.press('ControlOrMeta+s');
    await craftEntry.waitForAutosaveToComplete(page);
    firstCardId = await firstCard.getAttribute('id');

    // and check if the change is there
    await expect(page.locator('#' + firstCardId)).toContainText(editedText);
  });

  test('Check that added nested entry can be discarded', async ({
    page,
    baseURL,
    craftEntry,
  }) => {
    const slideout = page.locator(slideoutLocator);
    const matrixField = page.locator(matrixFieldLocator);

    // edit entry from previous test
    await craftEntry.editFirstEntryInElementIndexTable(page);

    // we need to turn the root entry into a draft or the second nested entry won't save against a draft via playwright in headless mode
    await page.locator('#slug').pressSequentially('test', {delay: 100});
    await page.locator('#content-notice .discard-changes-btn').waitFor();

    // add a second nested entry to the matrix
    await page.getByRole('button', {name: 'New entry'}).click();
    await slideout.locator(textFieldLocator).waitFor();
    await craftEntry.fillField(page, slideout, textFieldLocator, 'card 2');

    // wait for the draft card to be attached
    const lastCard = matrixField.locator(lastChildLocator);
    await lastCard.waitFor({state: 'attached'});

    // save the second nested entry
    await slideout.getByRole('button', {name: 'Create entry'}).click();

    // wait till save is done
    await craftEntry.waitForAutosaveToComplete(page);
    await lastCard.locator('.status-label-text:text-is("Live")').waitFor();

    // check the card was added and that the blue indicators are there and the field modified indicator has correct text
    await expect(lastCard).toContainText('card 2');
    await expect(
      matrixField.getByTitle(craftEntry.fieldModifiedText)
    ).toBeVisible();
    await expect(matrixField.getByTitle(craftEntry.newEntryText)).toBeVisible();

    // discard root entry changes
    await craftEntry.discardElementChanges(page);

    // check that there's no blue indicators
    await expect(
      matrixField.getByTitle(craftEntry.fieldModifiedText)
    ).not.toBeVisible();

    // and there's only one card in the matrix field
    await expect(matrixField.locator('.cards .card')).toHaveCount(1);
  });
});

test.describe('Static field', () => {
  const titleText = 'Entry with static cards matrix';
  const originalText = 'card 1';
  const matrixFieldLocator = '#fields-staticMatrixCardsField-field';
  const firstCardLocator = matrixFieldLocator + firstChildLocator;

  test('Check that when max entries limit is reached, you can’t add any more cards', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    const slideout = page.locator(slideoutLocator);
    const matrixField = page.locator(matrixFieldLocator);
    const newCardBtn = craftMatrix.getNewNestedEntryBtn(page);

    // create new entry that contains matrix field in cards view mode
    await craftEntry.createEntryBySectionName(page, 'Test Matrix');

    // switch to the "With Static Matrix in Cards mode" entry type
    await craftEntry.switchEntryTypeByName(
      page,
      'With Static Matrix in Cards mode'
    );
    await page.locator(matrixFieldLocator).waitFor({state: 'visible'});

    // fill out the title
    await craftEntry.fillField(page, page, titleFieldLocator, titleText, 100);

    // add one nested entry to the matrix,
    await newCardBtn.click();

    // fill out the field
    await craftEntry.fillField(page, slideout, textFieldLocator, originalText);

    // wait for the draft card to be attached
    const firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor({state: 'attached'});
    const firstCardId = await firstCard.getAttribute('id');

    // save nested entry
    await page.getByRole('button', {name: 'Create entry'}).click();

    // wait for the status of the card to get updated
    await firstCard.locator('.status-label-text:text-is("Live")').waitFor();

    // check that the new entry button for this static matrix field is disabled
    await expect(newCardBtn).toHaveCount(1);
    await expect(newCardBtn).toContainClass('disabled');

    // check that there's exactly one block in this static matrix field
    await expect(matrixField.locator('.cards .card')).toHaveCount(1);

    // save the root entry & continue editing
    await craftEntry.saveRootEntryAndContinueEditing(page);

    // check that the new entry button for this static matrix field is still disabled
    await expect(newCardBtn).toContainClass('disabled');

    // check that there's still exactly one card in this static matrix field
    await expect(matrixField.locator('.cards .card')).toHaveCount(1);
  });

  test('Check that the "Copy" action is available in static matrix', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    // edit entry from previous test
    await craftEntry.editEntryInElementIndexTableByTitle(page, titleText);

    const firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor({state: 'attached'});

    const actionsMenuId = await craftMatrix.getCardActionMenuId(firstCard);
    await craftMatrix.openCardActionMenu(firstCard);

    // check that we have one 'Copy' action in the card's actions menu
    await expect(
      craftMatrix.getElementActionElement(page, actionsMenuId, 'Copy')
    ).toHaveCount(1);
    // check that the 'Copy' action is visible
    await expect(
      craftMatrix.getElementActionElement(page, actionsMenuId, 'Copy')
    ).toBeVisible();
  });
});

test.describe('Max entries limit', () => {
  const titleText = 'Entry with max 2 cards matrix';
  const originalText = 'card';
  const matrixFieldLocator = '#fields-matrixCardsFieldMax2-field';
  const cardLocator = matrixFieldLocator + ' .cards .card';
  const firstCardLocator = matrixFieldLocator + firstChildLocator;

  test('Check "Duplicate" action and max entries limit', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    const slideout = page.locator(slideoutLocator);
    const matrixField = page.locator(matrixFieldLocator);
    const newCardBtn = craftMatrix.getNewNestedEntryBtn(page);

    // create new entry that contains matrix field in cards view mode
    await craftEntry.createEntryBySectionName(page, 'Test Matrix');

    // switch to the "With Max 2 Matrix in Blocks mode" entry type
    await craftEntry.switchEntryTypeByName(
      page,
      'With Max 2 Matrix in Cards mode'
    );
    await page.locator(matrixFieldLocator).waitFor({state: 'visible'});

    // fill the title
    await craftEntry.fillField(page, page, titleFieldLocator, titleText, 50);

    const firstCard = page.locator(firstCardLocator);

    // create two cards so that we reach the limit
    for (var i = 0; i < 2; i++) {
      // add one nested entry to the matrix,
      await newCardBtn.click();

      // fill out the field
      await craftEntry.fillField(
        page,
        slideout,
        textFieldLocator,
        originalText + ' ' + (i + 1)
      );

      // save nested entry
      await page.getByRole('button', {name: 'Create entry'}).click();

      const card = page.locator(matrixFieldLocator + lastChildLocator);
      await card.waitFor({state: 'attached'});

      // wait for the status of the card to get updated
      await card.locator('.status-label-text:text-is("Live")').waitFor();
    }

    // check that the new entry button is disabled
    await expect(newCardBtn).toContainClass('disabled');

    // check that "Duplicate" action is not available when you reach the limit
    let actionsMenuId = await craftMatrix.getCardActionMenuId(firstCard);
    await craftMatrix.openCardActionMenu(firstCard);
    await expect(
      craftMatrix.getElementActionElement(page, actionsMenuId, 'Duplicate')
    ).toHaveCount(0);

    // save the root entry & continue editing
    await craftEntry.saveRootEntryAndContinueEditing(page);

    // delete the first card
    actionsMenuId = await craftMatrix.getCardActionMenuId(firstCard);
    await craftMatrix.openCardActionMenu(firstCard);
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });
    await craftMatrix
      .getElementActionElement(page, actionsMenuId, 'Delete entry')
      .click();
    // wait for the action to complete
    await page.waitForLoadState('networkidle');
    await craftEntry.waitForAutosaveToComplete(page);

    // check that "Duplicate" action is not yet available
    actionsMenuId = await craftMatrix.getCardActionMenuId(firstCard);
    await craftMatrix.openCardActionMenu(firstCard);
    await expect(
      craftMatrix.getElementActionElement(page, actionsMenuId, 'Duplicate')
    ).toHaveCount(0);

    // reload the page
    await page.reload();
    await page.waitForLoadState();

    // check that "Duplicate" action is now available
    actionsMenuId = await craftMatrix.getCardActionMenuId(firstCard);
    await craftMatrix.openCardActionMenu(firstCard);
    await expect(
      craftMatrix.getElementActionElement(page, actionsMenuId, 'Duplicate')
    ).toHaveCount(1);
  });
});
