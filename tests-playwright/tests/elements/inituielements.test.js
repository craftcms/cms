/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test.beforeAll(async ({craftSetup}) => {
  await craftSetup.cleanAll();
  await craftSetup.loadFixture('Entry');
});

test.beforeEach(async ({page}) => {
  await page.goto('./entries/testInitUiElements');
});

// check if UI Elements are getting instantiated at the right time (after appending head and body html)
// details: https://github.com/craftcms/cms/issues/16554
test('Custom color fields instantiation', async ({
  page,
  baseURL,
  craftEntry,
}) => {
  const titleFieldLocator = '#title';
  const titleText = 'Entry with colours';

  // create new entry that contains matrix field in cards view mode
  await page.getByLabel('New entry in the Test Init UI Elements').click();
  await page.waitForLoadState();

  // set the title
  await page
    .locator(titleFieldLocator)
    .pressSequentially(titleText, {delay: 50});
  await craftEntry.waitForAutosaveToComplete(page);

  // save root entry
  await page.keyboard.press('ControlOrMeta+s');
  await craftEntry.waitForAutosaveToComplete(page);

  // preview
  const preview = page.locator('.lp-editor-container');

  // open preview
  await page.locator('#header .preview-btn').click();
  await preview.waitFor({state: 'visible'});

  await testCustomColour(page, preview, 'colour2');

  // close preview
  await preview.locator('.lp-editor-header .lp-close-btn').click();
  await preview.waitFor({state: 'hidden'});

  // editor
  const editor = page.locator('#content');
  await testCustomColour(page, editor, 'colour2');

  // matrix block
  const matrixBlocksFieldLocator = '#fields-matrixBlocksField2-field';
  const firstBlockLocator =
    matrixBlocksFieldLocator + ' .blocks > .matrixblock:first-child';
  const matrixBlocksField = page.locator(matrixBlocksFieldLocator);
  await matrixBlocksField.locator('.buttons button.add').click();
  await craftEntry.waitForAutosaveToComplete(page);

  // check if the block was attached to the dom
  const firstBlock = page.locator(firstBlockLocator);
  await firstBlock.waitFor({state: 'attached'});

  await testCustomColour(page, firstBlock, 'colour');

  // matrix card
  const matrixCardsFieldLocator = '#fields-matrixCardsField2-field';
  const firstCardLocator =
    matrixCardsFieldLocator + ' .cards > li:first-child .card';
  const slideoutLocator = '.slideout-container:not(.hidden)';
  const slideout = page.locator(slideoutLocator);

  await page
    .locator('#content ' + matrixCardsFieldLocator)
    .getByRole('button', {name: 'New entry'})
    .click();
  await craftEntry.waitForAutosaveToComplete(page);
  await testCustomColour(page, slideout, 'colour');
});

const testCustomColour = async (page, container, fieldHandle) => {
  const colourFieldLocator = 'div[id$="fields-' + fieldHandle + '-field"]';
  // select "custom" color option
  const selectizeInput = container.locator(
    colourFieldLocator + ' .selectize-input input'
  );
  const selectizeOptions = await selectizeInput.getAttribute('aria-owns');
  await container.locator(colourFieldLocator + ' .selectize-input').click();
  // we have to use div[id="VAL"] here because selectizeOptions value can start with a number and CSS3 doesn't support ID selectors that start with a digit
  // alternatively, you can also use xpath, like so: //div[@id="VAL"]')
  await page
    .locator('div[id="' + selectizeOptions + '"]')
    .waitFor({state: 'visible'});
  await page
    .locator('div[id="' + selectizeOptions + '"] div[data-value="__custom__"]')
    .click();

  // check that the extra field for specifying custom color is visible
  await expect(
    container.locator('div[id$="fields-' + fieldHandle + '-custom-__custom__"]')
  ).toBeVisible();
};
