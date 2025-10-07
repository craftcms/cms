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
const textFieldLocator = ' input[name$="[fields][plainTextField6]"]';
const newBlockLocator = '.buttons button.add';

test.describe('Create, edit, discard', () => {
  const titleText = 'Entry with blocks matrix';
  const originalText = 'card 1';
  const editedText = originalText + ' edited';
  const matrixFieldLocator = '#fields-matrixBlocksField-field';
  const blockLocator = matrixFieldLocator + ' .blocks > .matrixblock';
  const firstBlockLocator = blockLocator + ':first-child';

  test('Create new root entry with inline-editable blocks matrix field', async ({
    page,
    baseURL,
    craftEntry,
  }) => {
    const matrixField = page.locator(matrixFieldLocator);

    // create new entry that contains matrix field in inline-editable blocks view mode
    await craftEntry.createEntryBySectionName(page, 'Test Matrix');

    // switch to the "With Matrix in Blocks mode" entry type
    await craftEntry.switchEntryTypeByName(page, 'With Matrix in Blocks mode');
    await page.locator(matrixFieldLocator).waitFor({state: 'visible'});

    // fill the title
    await craftEntry.fillField(page, page, titleFieldLocator, titleText, 50);

    // add entry block to the matrix
    await matrixField.locator(newBlockLocator).click();
    await craftEntry.waitForAutosaveToComplete(page);

    // wait for the block to get attached to the dom
    const firstBlock = page.locator(firstBlockLocator);
    await firstBlock.waitFor({state: 'attached'});

    // fill out the plain text field
    await craftEntry.fillField(
      page,
      firstBlock,
      textFieldLocator,
      originalText
    );

    // check the field has the modified indicator
    await expect(
      page.locator(matrixFieldLocator + ' > .status-badge')
    ).toBeVisible();

    // and save the root entry
    await craftEntry.saveRootEntry(page);
  });

  test('Edit nested entry, save and check if the value saved', async ({
    page,
    baseURL,
    craftEntry,
  }) => {
    // edit entry from previous test
    await craftEntry.editEntryInElementIndexTableByTitle(page, titleText);

    // wait for the block to get attached to the dom
    const firstBlock = page.locator(firstBlockLocator);
    await firstBlock.waitFor({state: 'attached'});

    // update text field value
    await firstBlock.locator(textFieldLocator).clear();
    await craftEntry.fillField(page, firstBlock, textFieldLocator, editedText);

    // check that both modified indicators show and that the field modified indicator has correct text
    await expect(
      page.locator(matrixFieldLocator + ' > .status-badge')
    ).toBeVisible();
    await expect(
      page.locator(matrixFieldLocator + ' > .status-badge')
    ).toHaveAttribute('title', craftEntry.fieldModifiedText);
    await expect(
      firstBlock.getByTitle(craftEntry.fieldModifiedText)
    ).toBeVisible();

    // save root entry
    await page.keyboard.press('ControlOrMeta+s');
    await craftEntry.waitForAutosaveToComplete(page);

    // and check if the edited block text is there
    await expect(firstBlock.locator(textFieldLocator)).toHaveValue(editedText);
  });

  test('Check that added nested entry can be discarded', async ({
    page,
    baseURL,
    craftEntry,
  }) => {
    const matrixField = page.locator(matrixFieldLocator);
    const lastBlockLocator =
      matrixFieldLocator + ' .blocks > .matrixblock:last-child';

    // edit entry from previous test
    await craftEntry.editEntryInElementIndexTableByTitle(page, titleText);

    // we need to turn the root entry into a draft or the second nested entry won't save against a draft via playwright in headless mode
    await page.locator('#slug').pressSequentially('test', {delay: 100});
    await page.locator('#content-notice .discard-changes-btn').waitFor();

    // add a second nested entry to the matrix,
    await matrixField.locator(newBlockLocator).click();
    await craftEntry.waitForAutosaveToComplete(page);

    const secondBlock = page.locator(lastBlockLocator);
    await secondBlock.waitFor({state: 'attached'});
    await secondBlock.locator(textFieldLocator).waitFor();
    await secondBlock.locator(textFieldLocator).focus();
    await craftEntry.fillField(page, secondBlock, textFieldLocator, 'card 2');

    // check that the blue indicators are there and that the field indicator has correct text
    await expect(
      page.locator(matrixFieldLocator + ' > .status-badge')
    ).toBeVisible();
    await expect(
      page.locator(matrixFieldLocator + ' > .status-badge')
    ).toHaveAttribute('title', craftEntry.fieldModifiedText);
    await expect(
      secondBlock.getByTitle(craftEntry.fieldModifiedText)
    ).not.toBeVisible();

    // discard root entry changes
    await craftEntry.discardElementChanges(page);

    // check that there's no blue indicators
    await expect(matrixField.locator(' > .status-badge')).not.toBeVisible();

    // and there's only one card in the matrix field
    await expect(matrixField.locator('.blocks .matrixblock')).toHaveCount(1);
  });
});

test.describe('Static field', () => {
  const titleText = 'Entry with static blocks matrix';
  const originalText = 'card 1';
  const matrixFieldLocator = '#fields-staticMatrixBlocksField-field';
  const blockLocator = matrixFieldLocator + ' .blocks > .matrixblock';
  const firstBlockLocator = blockLocator + ':first-child';

  // check that when creating an entry with a static matrix field, the block gets added automatically
  test('Check that static blocks get added automatically', async ({
    page,
    baseURL,
    craftEntry,
  }) => {
    const matrixField = page.locator(matrixFieldLocator);

    // create new entry that contains matrix field in cards view mode
    await craftEntry.createEntryBySectionName(page, 'Test Matrix');

    // switch to the "With Static Matrix in Blocks mode" entry type
    await craftEntry.switchEntryTypeByName(
      page,
      'With Static Matrix in Blocks mode'
    );
    await page.locator(matrixFieldLocator).waitFor({state: 'visible'});

    // wait for the first block to get attached
    const firstBlock = page.locator(firstBlockLocator);
    await firstBlock.waitFor({state: 'attached'});

    // check that there's no new block button for this static matrix field
    await expect(matrixField.locator(newBlockLocator)).toHaveCount(0);

    // check that there's exactly one block in this static matrix field
    await expect(matrixField.locator('.blocks .matrixblock')).toHaveCount(1);

    // fill out the title
    await craftEntry.fillField(page, page, titleFieldLocator, titleText, 50);

    // fill out the nested entry's plain text field
    await craftEntry.fillField(
      page,
      firstBlock,
      textFieldLocator,
      originalText
    );

    // save the root entry & continue editing
    await craftEntry.saveRootEntryAndContinueEditing(page);

    // check that there's still no new block button for this static matrix field
    await expect(matrixField.locator(newBlockLocator)).toHaveCount(0);

    // check that there's still exactly one block in this static matrix field
    await expect(matrixField.locator('.blocks .matrixblock')).toHaveCount(1);
  });

  test('Check that the "Copy" action is available in static blocks', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    // edit entry from previous test
    await craftEntry.editEntryInElementIndexTableByTitle(page, titleText);

    const firstBlock = page.locator(firstBlockLocator);

    const actionsMenuId = await craftMatrix.getBlockActionMenuId(firstBlock);
    await craftMatrix.openBlockActionMenu(firstBlock);

    // check that we have one 'Copy' action in the block's actions menu
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
  const titleText = 'Entry with max 2 blocks matrix';
  const originalText = 'card';
  const matrixFieldLocator = '#fields-matrixBlocksFieldMax2-field';
  const blockLocator = matrixFieldLocator + ' .blocks > .matrixblock';
  const firstBlockLocator = blockLocator + ':first-child';

  // check that when creating an entry with a static matrix field, the block gets added automatically
  test('Check Duplicate action and max entries limit', async ({
    page,
    baseURL,
    craftEntry,
    craftMatrix,
  }) => {
    const matrixField = page.locator(matrixFieldLocator);

    // create new entry that contains matrix field in cards view mode
    await craftEntry.createEntryBySectionName(page, 'Test Matrix');

    // switch to the "With Max 2 Matrix in Blocks mode" entry type
    await craftEntry.switchEntryTypeByName(
      page,
      'With Max 2 Matrix in Blocks mode'
    );
    await page.locator(matrixFieldLocator).waitFor({state: 'visible'});

    // fill the title
    await craftEntry.fillField(page, page, titleFieldLocator, titleText, 50);

    const firstBlock = page.locator(blockLocator + ':first-child');

    // create two blocks so that we reach the limit
    for (var i = 0; i < 2; i++) {
      // add entry block to the matrix,
      await matrixField.locator(newBlockLocator).click();
      await craftEntry.waitForAutosaveToComplete(page);

      // check if the block was attached to the dom
      var block = page.locator(blockLocator + ':last-child');
      await block.waitFor({state: 'attached'});

      // fill out the plain text field
      await craftEntry.fillField(
        page,
        block,
        textFieldLocator,
        originalText + ' ' + (i + 1)
      );
    }

    // check that the new entry button is disabled
    await expect(matrixField.locator(newBlockLocator)).toBeDisabled();

    // check that "Duplicate" action is not available when you reach the limit
    let actionsMenuId = await craftMatrix.getBlockActionMenuId(firstBlock);
    await craftMatrix.openBlockActionMenu(firstBlock);
    await expect(
      craftMatrix.getElementActionElement(page, actionsMenuId, 'Duplicate')
    ).toHaveCount(0);

    // save root entry and continue editing
    await craftEntry.saveRootEntryAndContinueEditing(page);

    // delete the first block
    actionsMenuId = await craftMatrix.getBlockActionMenuId(firstBlock);
    await craftMatrix.openBlockActionMenu(firstBlock);
    await craftMatrix
      .getElementActionElement(page, actionsMenuId, 'Delete')
      .click();
    // wait for the action to complete
    await page.waitForLoadState('networkidle');
    await craftEntry.waitForAutosaveToComplete(page);

    // check that "Duplicate" action is now available
    actionsMenuId = await craftMatrix.getBlockActionMenuId(firstBlock);
    await craftMatrix.openBlockActionMenu(firstBlock);
    await expect(
      craftMatrix.getElementActionElement(page, actionsMenuId, 'Duplicate')
    ).toHaveCount(1);
  });
});
