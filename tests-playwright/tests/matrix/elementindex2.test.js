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

    // create new entry that contains matrix field in cards view mode
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

      const card = page.locator(matrixFieldLocator + lastCardLocator);
      await card.waitFor({state: 'attached'});

      // wait for the status of the card to get updated
      await card.locator('.status-label-text:text-is("Live")').waitFor();
    }

    // check that the new entry button is disabled
    await expect(newCardBtn).toContainClass('disabled');

    // select the card
    await firstCard.waitFor({state: 'attached'});
    firstCard.click();

    // activate actions btn
    await craftMatrix.openNestedElementIndexActionMenu(
      page,
      matrixContainerLocator
    );

    // check that "Duplicate" action is not available when you reach the limit
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

    await craftEntry.saveRootEntryAndContinueEditing(page);

    // select the card
    await firstCard.waitFor({state: 'attached'});
    firstCard.click();

    // activate actions btn
    await craftMatrix.openNestedElementIndexActionMenu(
      page,
      matrixContainerLocator
    );

    // accept deleting
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    await craftMatrix
      .getElementIndexActionElement(page, matrixContainerLocator, 'Delete')
      .click();
    await page.waitForLoadState('networkidle');
    await craftEntry.waitForAutosaveToComplete(page);

    // select the card
    await firstCard.waitFor({state: 'attached'});
    firstCard.click();

    // activate actions btn
    await craftMatrix.openNestedElementIndexActionMenu(
      page,
      matrixContainerLocator
    );

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
    ).not.toContainClass('disabled');

    await page.reload();
    await page.waitForLoadState();
    await craftEntry.waitForAutosaveToComplete(page);

    // check that "Duplicate" action is now available
    // select the card
    await firstCard.waitFor({state: 'attached'});
    await firstCard.click();

    // activate actions btn
    await craftMatrix.openNestedElementIndexActionMenu(
      page,
      matrixContainerLocator
    );

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
    ).not.toContainClass('disabled');
  });
});
