/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

const slideoutLocator = '.slideout-container:not(.hidden)';

/**
 * Address labels are made unique per run so leftover cards from an earlier
 * run (e.g. when the database isn't restored between runs) can't collide
 * with this run's locators.
 */
function uniqueLabel(name) {
  return `${name} ${Date.now()}`;
}

test.beforeAll(async ({craftSetup}) => {
  await craftSetup.cleanAll();
});

test.beforeEach(async ({page}) => {
  await page.goto('./myaccount/addresses');
});

/**
 * A card in the address grid, singled out by its label.
 */
function cardByLabel(page, label) {
  return page.locator('craft-card').filter({hasText: label});
}

/**
 * Opens a card's action menu and returns the menu item with the given label.
 */
async function openCardActionItem(page, card, itemLabel) {
  await card.getByRole('button', {name: 'Actions'}).click();
  return card.locator('craft-action-item').filter({hasText: itemLabel});
}

/**
 * Creates an address through the "New address" flow and waits for its card
 * to appear.
 */
async function createAddress(page, label) {
  const slideout = page.locator(slideoutLocator);

  await page.getByRole('button', {name: 'New address'}).click();
  await slideout.waitFor();

  await slideout.locator('input[name$="[title]"]').fill(label);
  await slideout
    .locator('input[name$="[addressLine1]"]')
    .fill('123 Main Street');
  await slideout.locator('input[name$="[locality]"]').fill('Portland');
  await slideout.locator('input[name$="[postalCode]"]').fill('97201');

  await page.getByRole('button', {name: 'Create address'}).click();
  await slideout.waitFor({state: 'hidden'});

  await expect(cardByLabel(page, label)).toBeVisible();
}

test.describe('Creating addresses', () => {
  test('The New address button opens an editor slideout', async ({page}) => {
    await page.getByRole('button', {name: 'New address'}).click();

    const slideout = page.locator(slideoutLocator);
    await expect(slideout.locator('input[name$="[title]"]')).toBeVisible();
    await expect(
      page.getByRole('button', {name: 'Create address'})
    ).toBeVisible();

    await page.getByRole('button', {name: 'Cancel'}).click();
    await slideout.waitFor({state: 'hidden'});
  });

  test('A created address appears as a card', async ({page}) => {
    const label = uniqueLabel('Created address');

    await createAddress(page, label);

    const card = cardByLabel(page, label);
    await expect(card).toContainText('123 Main Street');
    await expect(card).toContainText('Portland');
  });
});

test.describe('Editing addresses', () => {
  test('Edits made in the slideout show on the card after saving', async ({
    page,
  }) => {
    const label = uniqueLabel('Edit target');
    const editedLabel = label + ' (edited)';

    await createAddress(page, label);

    const slideout = page.locator(slideoutLocator);
    await cardByLabel(page, label)
      .getByRole('button', {name: 'Edit address'})
      .click();
    await slideout.waitFor();

    await expect(slideout.locator('input[name$="[title]"]')).toHaveValue(label);
    await slideout.locator('input[name$="[title]"]').fill(editedLabel);
    await slideout
      .locator('input[name$="[addressLine1]"]')
      .fill('1 Edited Road');
    await page.getByRole('button', {name: 'Save', exact: true}).click();
    await slideout.waitFor({state: 'hidden'});

    const card = cardByLabel(page, editedLabel);
    await expect(card).toBeVisible();
    await expect(card).toContainText('1 Edited Road');
  });

  test('Cancelling an edit leaves the card unchanged', async ({page}) => {
    const label = uniqueLabel('Cancelled edit');
    const discardedLabel = label + ' (discarded)';

    await createAddress(page, label);

    const slideout = page.locator(slideoutLocator);
    await cardByLabel(page, label)
      .getByRole('button', {name: 'Edit address'})
      .click();
    await slideout.waitFor();

    await slideout.locator('input[name$="[title]"]').fill(discardedLabel);

    // Cancelling a dirty editor asks whether to discard the changes.
    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', {name: 'Cancel'}).click();
    await slideout.waitFor({state: 'hidden'});

    await expect(cardByLabel(page, label)).toBeVisible();
    await expect(cardByLabel(page, discardedLabel)).toHaveCount(0);
  });
});

test.describe('Deleting addresses', () => {
  test('Deleting an address removes its card', async ({page}) => {
    const label = uniqueLabel('Delete target');

    await createAddress(page, label);

    const card = cardByLabel(page, label);
    const deleteItem = await openCardActionItem(page, card, 'Delete address');

    // Deleting asks for confirmation first.
    page.once('dialog', (dialog) => dialog.accept());
    await deleteItem.click();

    await expect(card).toHaveCount(0);
  });

  test('Dismissing the confirmation keeps the address', async ({page}) => {
    const label = uniqueLabel('Kept address');

    await createAddress(page, label);

    const card = cardByLabel(page, label);
    const deleteItem = await openCardActionItem(page, card, 'Delete address');

    page.once('dialog', (dialog) => dialog.dismiss());
    await deleteItem.click();

    await expect(card).toBeVisible();
  });
});

test.describe('Duplicating addresses', () => {
  test('Duplicating an address adds a second card with the same content', async ({
    page,
  }) => {
    const label = uniqueLabel('Duplicate target');

    await createAddress(page, label);

    const card = cardByLabel(page, label).first();
    const duplicateItem = await openCardActionItem(page, card, 'Duplicate');
    await duplicateItem.click();

    await expect(cardByLabel(page, label)).toHaveCount(2);
  });
});

test.describe('Copying addresses', () => {
  test('Copying an address shows a copied notification', async ({page}) => {
    const label = uniqueLabel('Copy target');

    await createAddress(page, label);

    const card = cardByLabel(page, label);
    const copyItem = await openCardActionItem(page, card, 'Copy address');
    await copyItem.click();

    await expect(page.getByText(/copied/i)).toBeVisible();
  });

  test('A copied address can be pasted as a new card', async ({page}) => {
    const label = uniqueLabel('Paste source');

    await createAddress(page, label);

    const card = cardByLabel(page, label);
    const copyItem = await openCardActionItem(page, card, 'Copy address');
    await copyItem.click();

    // The copied element is offered as a paste action.
    await page.getByRole('button', {name: /Paste/}).click();

    await expect(cardByLabel(page, label)).toHaveCount(2);
  });
});
