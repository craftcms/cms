const {test, expect} = require('@craftcms/playwright');
const craft = require('@craftcms/playwright/_craft');
const entries = require('@craftcms/playwright/helpers/entries');

test.beforeAll(async ({}) => {
  await craft.cleanAll();
  await craft.loadFixture('Entry');
});

test.beforeEach(async ({page}) => {
  await page.goto('./entries/testMatrix');
});

test.describe('Blocks', () => {
  const titleText = 'Entry with blocks matrix';
  const originalText = 'card 1';
  const editedText = originalText + ' edited';
  const titleFieldLocator = '#title';
  const matrixBlocksFieldLocator = '#fields-matrixBlocksField-field';
  const firstBlockLocator = matrixBlocksFieldLocator + ' .blocks > .matrixblock:first-child';
  const textFieldLocator = ' input[name$="[fields][plainTextField6]"]';
  const newBlockLocator = '.buttons button.add';

  // create new entry that contains matrix field in inline-editable blocks view mode
  // add nested entry (block) to the matrix,
  // check the block was added and that the matrix field and edited fields don't have the modified indicator
  // and save the root entry
  test('Create new root entry with inline-editable blocks matrix field', async ({page, baseURL}) => {
    const matrixBlocksField = page.locator(matrixBlocksFieldLocator);

    // create new entry that contains matrix field in cards view mode
    await page.getByLabel('New entry in the Test Matrix').click();
    await page.waitForLoadState();

    // switch to the "With Matrix in Element Index mode" entry type
    await page.locator('#entryType-button').click();
    await page.getByRole('button', { name: 'With Matrix in Blocks mode' }).click();

    // wait for the loader to disappear
    await entries.waitForAutosaveToComplete(page);
    await page.locator(matrixBlocksFieldLocator).waitFor({state: 'visible'});

    // set the title
    await page.locator(titleFieldLocator).pressSequentially(titleText, {delay: 50});
    await entries.waitForAutosaveToComplete(page);

    // add entry block to the matrix,
    await matrixBlocksField.locator(newBlockLocator).click();
    await entries.waitForAutosaveToComplete(page);

    // check if the block was attached to the dom
    const firstBlock = page.locator(firstBlockLocator);
    await firstBlock.waitFor({state: 'attached'});

    // fill out the field
    await firstBlock.locator(textFieldLocator).pressSequentially(originalText, { delay: 100 });
    await entries.waitForAutosaveToComplete(page);

    // check the block was added and the field has the modified indicator
    await expect(page.locator(matrixBlocksFieldLocator + ' > .status-badge')).toBeVisible();
    //await expect(firstBlock.getByTitle(entries.fieldModifiedText)).not.toBeVisible();

    // and save the root entry
    await page.getByRole('button', { name: 'Create entry' }).click();
  });

  // edit entry from previous test
  // check that the entry (block) nested in a matrix can be edited
  // update text field value
  // check that the modified indicators show (for both the matrix field and the edited text field)
  // save root entry and check if the change is there
  test('Edit nested entry, save and check if the value saved', async ({page, baseURL}) => {
    // edit entry from previous test
    await entries.editFirstEntryInElementIndexTable(page);

    // check if the block was attached to the dom
    const firstBlock = page.locator(firstBlockLocator);
    await firstBlock.waitFor({state: 'attached'});

    // update text field value
    await firstBlock.locator(textFieldLocator).clear();
    await firstBlock.locator(textFieldLocator).pressSequentially(editedText, { delay: 100 });
    await entries.waitForAutosaveToComplete(page);

    // check that both modified indicators show
    await expect(page.locator(matrixBlocksFieldLocator + ' > .status-badge')).toBeVisible();
    await expect(page.locator(matrixBlocksFieldLocator + ' > .status-badge')).toHaveAttribute('title', entries.fieldModifiedText);
    await expect(firstBlock.getByTitle(entries.fieldModifiedText)).toBeVisible();

    // save root entry
    await page.keyboard.press('ControlOrMeta+s');
    await entries.waitForAutosaveToComplete(page);

    // and check if the change is there
    await expect(firstBlock.locator(textFieldLocator)).toHaveValue(editedText);
  });

  // edit entry from previous test
  // add a second nested entry (block) to the matrix
  // check the block was added and that the blue indicator for the matrix field is there
  // discard root entry changes
  // check that there's no blue indicator and there's only one block in the matrix field
  test('Check that added nested entry can be discarded', async ({page, baseURL}) => {
    const matrixBlocksField = page.locator(matrixBlocksFieldLocator);
    const lastBlockLocator = matrixBlocksFieldLocator + ' .blocks > .matrixblock:last-child';

    // edit entry from previous test
    await entries.editFirstEntryInElementIndexTable(page);

    // we need to turn the root entry into a draft or the second nested entry won't save against a draft via playwright in headless mode
    await page.locator('#slug').pressSequentially('test', { delay: 100 });
    await page.locator('#content-notice .discard-changes-btn').waitFor();

    // add a second nested entry to the matrix,
    await matrixBlocksField.locator(newBlockLocator).click();
    await entries.waitForAutosaveToComplete(page);

    const secondBlock = page.locator(lastBlockLocator);
    await secondBlock.waitFor({state: 'attached'});

    await secondBlock.locator(textFieldLocator).waitFor();
    await secondBlock.locator(textFieldLocator).focus();
    await secondBlock.locator(textFieldLocator).pressSequentially('card 2', { delay: 100 });

    // wait till save is done
    await entries.waitForAutosaveToComplete(page);

    // check that the blue indicators are there
    await expect(page.locator(matrixBlocksFieldLocator + ' > .status-badge')).toBeVisible();
    await expect(page.locator(matrixBlocksFieldLocator + ' > .status-badge')).toHaveAttribute('title', entries.fieldModifiedText);
    await expect(secondBlock.getByTitle(entries.fieldModifiedText)).not.toBeVisible();

    // discard root entry changes
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });
    await page.locator('#content .discard-changes-btn').click();
    await page.waitForLoadState();

    // check that there's no blue indicators
    await expect(matrixBlocksField.locator(' > .status-badge')).not.toBeVisible();

    // and there's only one card in the matrix field
    await expect(matrixBlocksField.locator('.blocks .matrixblock')).toHaveCount(
      1
    );
  });
});
