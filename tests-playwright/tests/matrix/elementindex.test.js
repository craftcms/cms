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

test.describe('Create, edit, discard', () => {
  const titleText = 'Entry with element index matrix';
  const originalText = 'card 1';
  const editedText = originalText + ' edited';
  const matrixContainerLocator = '#fields-matrixElementIndexField';
  const matrixFieldLocator = matrixContainerLocator + '-field';
  const firstCardLocator =
    matrixFieldLocator + ' .card-grid > li:first-child .card';

  test('Create new root entry with element index matrix field', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    const slideout = page.locator(slideoutLocator);

    // create new entry that contains matrix field in element index view mode
    await craftEntry.createEntryBySectionName(page, 'Test Matrix');

    // switch to the "With Matrix in Element Index mode" entry type
    await craftEntry.switchEntryTypeByName(
      page,
      'With Matrix in Element Index'
    );
    await page.locator(matrixContainerLocator).waitFor({state: 'visible'});

    // fill the title
    await craftEntry.fillField(page, page, titleFieldLocator, titleText, 50);

    // add nested entry to the matrix
    await craftMatrix.getNewNestedEntryBtn(page).click();
    await craftEntry.waitForAutosaveToComplete(page);

    // fill out the plain text field
    await craftEntry.fillField(page, slideout, textFieldLocator, originalText);

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
      page.locator(matrixFieldLocator).getByTitle(craftEntry.fieldModifiedText)
    ).toBeVisible();

    // check that the nested entry can be edited after being created
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
    await craftEntry.editEntryInElementIndexTableByTitle(page, titleText);

    let firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor();
    let firstCardId = await firstCard.getAttribute('id');

    // check that the entry nested in a matrix can be edited
    await craftMatrix.getEditNestedEntryBtn(page, firstCardId).click();

    // update text field value
    await slideout.locator(textFieldLocator).waitFor();
    await slideout.locator(textFieldLocator).clear();
    await craftEntry.fillField(page, slideout, textFieldLocator, editedText);

    // close the slideout without saving
    await slideout.locator('.so-footer .discard-changes-btn').waitFor();
    await slideout.getByRole('button', {name: 'Close'}).click();

    // check that the card has the Edited status pill
    await expect(
      firstCard.locator(
        '.card-body ul:last-child li:last-child .status-label-text'
      )
    ).toContainText('Edited');

    // open the slideout again, and save
    await craftMatrix.getEditNestedEntryBtn(page, firstCardId).click();
    await slideout.getByRole('button', {name: 'Save'}).click();
    await craftEntry.waitForAutosaveToComplete(page);

    // check that the field modified indicator doesn't show and there's only one status pill
    await expect(
      page.locator(matrixFieldLocator).getByTitle(craftEntry.fieldModifiedText)
    ).not.toBeVisible();
    await expect(firstCard.locator('.status-label')).toHaveCount(1);

    // save root entry
    await craftEntry.saveRootEntryAndContinueEditing(page);

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

    // edit entry from previous test
    await craftEntry.editEntryInElementIndexTableByTitle(page, titleText);

    // we need to turn the root entry into a draft or the second nested entry won't save against a draft via playwright in headless mode
    await page.locator('#slug').pressSequentially('test', {delay: 100});
    await page.locator('#content-notice .discard-changes-btn').waitFor();

    // add a second nested entry to the matrix
    await page.getByRole('button', {name: 'New entry'}).click();
    await slideout.locator(textFieldLocator).waitFor();
    await craftEntry.fillField(page, slideout, textFieldLocator, 'card 2');

    // wait for the draft card to be attached
    const lastCard = page.locator(
      matrixContainerLocator + ' .card-grid > li:last-child .card'
    );
    await lastCard.waitFor({state: 'attached'});

    // save the second nested entry
    await slideout.getByRole('button', {name: 'Create entry'}).click();

    // wait till save is done
    await craftEntry.waitForAutosaveToComplete(page);
    await lastCard.locator('.status-label-text:text-is("Live")').waitFor();

    // check the card was added and that the blue indicators are there and the field modified indicator has correct text
    await expect(lastCard).toContainText('card 2');
    await expect(
      page.locator(matrixFieldLocator).getByTitle(craftEntry.fieldModifiedText)
    ).toBeVisible();
    await expect(lastCard.getByTitle(craftEntry.newEntryText)).toBeVisible();

    // discard root entry changes
    await craftEntry.discardElementChanges(page);

    // check that there's no blue indicators
    await expect(
      page.locator(matrixFieldLocator).getByTitle(craftEntry.fieldModifiedText)
    ).not.toBeVisible();

    // and there's only one card in the matrix field
    await expect(
      page.locator(matrixContainerLocator + ' .card-grid .card')
    ).toHaveCount(1);
  });
});

test.describe('Static field', () => {
  const titleText = 'Entry with static element index matrix';
  const originalText = 'card 1';
  const matrixContainerLocator = '#fields-staticMatrixElementIndexField';
  const matrixFieldLocator = matrixContainerLocator + '-field';
  const firstCardLocator =
    matrixFieldLocator + ' .card-grid li:first-child .card';

  // check that when creating an entry with a static matrix field, adding max number of cards blocks adding more
  test('Check that when max entries limit is reached, you can’t add any more cards', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    const slideout = page.locator(slideoutLocator);
    const matrixField = page.locator(matrixFieldLocator);
    const newCardBtn = craftMatrix.getNewNestedEntryBtn(page);

    // create new entry that contains matrix field in element index view mode
    await craftEntry.createEntryBySectionName(page, 'Test Matrix');

    // switch to the "With Static Matrix in Element index mode" entry type
    await craftEntry.switchEntryTypeByName(
      page,
      'With Static Matrix in Element Index mode'
    );
    await page.locator(matrixFieldLocator).waitFor({state: 'visible'});

    // fill out the title
    await craftEntry.fillField(page, page, titleFieldLocator, titleText, 100);

    // add one nested entry to the matrix,
    await newCardBtn.click();

    // fill out the plain text field
    await craftEntry.fillField(page, slideout, textFieldLocator, originalText);

    // wait for the draft card was attached to the dom
    const firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor({state: 'attached'});
    const firstCardId = await firstCard.getAttribute('id');

    // save nested entry
    await page.getByRole('button', {name: 'Create entry'}).click();

    // wait for the status of the card to get updated
    await firstCard.locator('.status-label-text:text-is("Live")').waitFor();

    // check that the new entry button for this static matrix field has a "disabled" class
    await expect(newCardBtn).toHaveCount(1);
    await expect(newCardBtn).toContainClass('disabled');

    // check that there's exactly one card in this static matrix field
    await expect(matrixField.locator('.card-grid .card')).toHaveCount(1);

    // save the root entry & continue editing
    await craftEntry.saveRootEntryAndContinueEditing(page);

    // check that the new entry button for this static matrix field still has a "disabled" class
    await expect(newCardBtn).toContainClass('disabled');

    // check that there's still exactly one card in this static matrix field
    await expect(matrixField.locator('.card-grid .card')).toHaveCount(1);
  });

  test('Check that the "Copy" action is available in static matrix', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    const matrixField = page.locator(matrixFieldLocator);

    // edit entry from previous test
    await craftEntry.editEntryInElementIndexTableByTitle(page, titleText);

    const firstCard = page.locator(firstCardLocator);
    await firstCard.waitFor({state: 'attached'});

    // select the card
    firstCard.click();

    // activate actions btn
    await craftMatrix.openNestedElementIndexActionMenu(
      page,
      matrixContainerLocator
    );

    // check that we have one 'Copy' action in the card's actions menu
    await expect(
      craftMatrix.getElementIndexActionElement(
        page,
        matrixContainerLocator,
        'Copy'
      )
    ).toHaveCount(1);
    // check that the 'Copy' action is visible
    await expect(
      craftMatrix.getElementIndexActionElement(
        page,
        matrixContainerLocator,
        'Copy'
      )
    ).toBeVisible();
  });
});

test.describe('Max entries limit', () => {
  const titleText = 'Entry with max 2 cards matrix';
  const originalText = 'card';
  const matrixContainerLocator = '#fields-matrixElementIndexFieldMax2';
  const matrixFieldLocator = matrixContainerLocator + '-field';
  const firstCardLocator =
    matrixFieldLocator + ' .card-grid > li:first-child .card';
  const lastCardLocator = ' .card-grid li:last-child .card';
  const cardLocator = matrixFieldLocator + ' .card-grid .card';

  test('Check "Duplicate" action and max entries limit', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    const slideout = page.locator(slideoutLocator);
    const matrixCardsField = page.locator(matrixFieldLocator);
    const newCardBtn = craftMatrix.getNewNestedEntryBtn(page);

    // create new entry that contains matrix field in element index view mode
    await craftEntry.createEntryBySectionName(page, 'Test Matrix');

    // switch to the "With Max 2 Matrix in Element Index mode" entry type
    await craftEntry.switchEntryTypeByName(
      page,
      'With Max 2 Matrix in Element Index mode'
    );
    await page.locator(matrixFieldLocator).waitFor({state: 'visible'});

    // fill the title
    await craftEntry.fillField(page, page, titleFieldLocator, titleText, 50);

    const firstCard = page.locator(firstCardLocator);

    // create two cards so that we reach the limit
    for (var i = 0; i < 2; i++) {
      // add one nested entry to the matrix
      await newCardBtn.click();

      // fill out the plain text field
      await craftEntry.fillField(
        page,
        slideout,
        textFieldLocator,
        originalText + ' ' + (i + 1)
      );

      // save nested entry
      await page.getByRole('button', {name: 'Create entry'}).click();

      const card = page.locator(matrixFieldLocator + lastCardLocator);
      await card.waitFor({state: 'attached'});

      // wait for the status of the card to get updated
      await card.locator('.status-label-text:text-is("Live")').waitFor();
    }

    // check that the new entry button has a "disabled" class
    await expect(newCardBtn).toContainClass('disabled');

    // select the card
    await firstCard.waitFor({state: 'attached'});
    firstCard.click();

    // activate actions btn
    await craftMatrix.openNestedElementIndexActionMenu(
      page,
      matrixContainerLocator
    );

    // check that "Duplicate" action is there but has a "disabled" class when you reach the limit
    await expect(
      craftMatrix.getElementIndexActionElement(
        page,
        matrixContainerLocator,
        'Duplicate'
      )
    ).toHaveCount(1);
    await expect(
      craftMatrix.getElementIndexActionElement(
        page,
        matrixContainerLocator,
        'Duplicate'
      )
    ).toContainClass('disabled');

    // save root entry
    await craftEntry.saveRootEntryAndContinueEditing(page);

    // select the card
    await firstCard.waitFor({state: 'attached'});
    firstCard.click();

    // activate actions btn
    await craftMatrix.openNestedElementIndexActionMenu(
      page,
      matrixContainerLocator
    );

    // delete the first card
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });
    await craftMatrix
      .getElementIndexActionElement(page, matrixContainerLocator, 'Delete')
      .click();
    // wait for the action to complete
    await page.waitForLoadState('networkidle');
    await craftEntry.waitForAutosaveToComplete(page);

    // select the first card
    await firstCard.waitFor({state: 'attached'});
    firstCard.click();

    // activate actions btn
    await craftMatrix.openNestedElementIndexActionMenu(
      page,
      matrixContainerLocator
    );

    // check that "Duplicate" action is there
    await expect(
      craftMatrix.getElementIndexActionElement(
        page,
        matrixContainerLocator,
        'Duplicate'
      )
    ).toHaveCount(1);
    // and that it doesn't have a "disabled" class
    await expect(
      craftMatrix.getElementIndexActionElement(
        page,
        matrixContainerLocator,
        'Duplicate'
      )
    ).not.toContainClass('disabled');

    // reload the page
    await page.reload();
    await page.waitForLoadState();
    await craftEntry.waitForAutosaveToComplete(page);

    // select the card
    await firstCard.waitFor({state: 'attached'});
    await firstCard.click();

    // activate actions btn
    await craftMatrix.openNestedElementIndexActionMenu(
      page,
      matrixContainerLocator
    );

    // check that "Duplicate" action is available
    await expect(
      craftMatrix.getElementIndexActionElement(
        page,
        matrixContainerLocator,
        'Duplicate'
      )
    ).toHaveCount(1);
    // and it doesn't have a "disabled" class
    await expect(
      craftMatrix.getElementIndexActionElement(
        page,
        matrixContainerLocator,
        'Duplicate'
      )
    ).not.toContainClass('disabled');
  });
});
