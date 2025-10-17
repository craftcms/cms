/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test.beforeAll(async ({craftSetup}) => {
  await craftSetup.cleanAll();
  await craftSetup.loadFixture('Entry');
});

test.beforeEach(async ({page}) => {
  await page.goto('./entries/testSorting');
});

test('Check customizing view', async ({page, baseURL}) => {
  const viewBtnSelector = '#toolbar button:has-text("View")';
  const viewBtn = page.locator(viewBtnSelector);
  await viewBtn.waitFor();
  await page.click(viewBtnSelector);

  let menuId = await viewBtn.getAttribute('aria-controls');
  menuId = menuId.replace('.', '\\.');

  let cols = ['Number Field', 'Plain Text Field5', 'Entry Type', 'ID'];

  for (let i = 0; i < cols.length; i++) {
    const itemSelector =
      '#' + menuId + ' .table-columns-field label:text-is("' + cols[i] + '")';
    const item = page.locator(itemSelector);
    await item.waitFor();
    await page.click(itemSelector);
  }

  const closeSelector = '#' + menuId + ' button:has-text("Close")';
  const close = page.locator(closeSelector);
  await close.waitFor();
  await page.click(closeSelector);

  for (let i = 0; i < cols.length; i++) {
    await expect(page.locator('.elements table.data thead')).toContainText(
      cols[i]
    );
  }
});

test.describe('Sorting', () => {
  const viewBtnSelector = '#toolbar button:has-text("View")';
  let cols = [
    {
      field: 'Number Field',
      firstTitle: 'Test sorting 1',
      lastTitle: 'Test sorting 10',
    },
    {
      field: 'Plain Text Field5',
      firstTitle: 'Test sorting 1',
      lastTitle: 'Test sorting 3',
    },
    {
      field: 'ID',
      firstTitle: 'Test sorting 1',
      lastTitle: 'Test sorting 10',
    },
  ];

  test('Check sort asc', async ({page, baseURL}) => {
    // test sort ascending
    for (let i = 0; i < cols.length; i++) {
      const viewBtn2 = page.locator(viewBtnSelector);
      await viewBtn2.waitFor();
      await page.click(viewBtnSelector);

      let menuId = await viewBtn2.getAttribute('aria-controls');
      menuId = menuId.replace('.', '\\.');

      const sortDropdownSelector = '#' + menuId + ' .sort-field select';
      const sortAscendingSelector =
        '#' + menuId + ' section button[data-dir="asc"]';
      const sortDescendingSelector =
        '#' + menuId + ' section button[data-dir="desc"]';
      const closeSelector = '#' + menuId + ' button:has-text("Close")';

      await page
        .locator(sortDropdownSelector)
        .selectOption({label: cols[i].field});
      await page.click(sortAscendingSelector);
      await page.click(closeSelector);

      await expect(
        page
          .locator('.elements table.data tbody tr')
          .nth(0)
          .locator('th[data-title="Entry"] .label-link')
      ).toContainText(cols[i]['firstTitle']);

      await page.reload();
    }
  });

  test('Check sort desc', async ({page, baseURL}) => {
    // test sort descending
    for (let i = 0; i < cols.length; i++) {
      const viewBtn3 = page.locator(viewBtnSelector);
      await viewBtn3.waitFor();
      await page.click(viewBtnSelector);

      let menuId = await viewBtn3.getAttribute('aria-controls');
      menuId = menuId.replace('.', '\\.');

      const sortDropdownSelector = '#' + menuId + ' .sort-field select';
      const sortAscendingSelector =
        '#' + menuId + ' section button[data-dir="asc"]';
      const sortDescendingSelector =
        '#' + menuId + ' section button[data-dir="desc"]';
      const closeSelector = '#' + menuId + ' button:has-text("Close")';

      await page
        .locator(sortDropdownSelector)
        .selectOption({label: cols[i].field});
      await page.click(sortAscendingSelector);
      await page.click(sortDescendingSelector);
      await page.click(closeSelector);

      await expect(
        page
          .locator('.elements table.data tbody tr')
          .nth(0)
          .locator('th[data-title="Entry"] .label-link')
      ).toContainText(cols[i]['lastTitle']);

      await page.reload();
    }
  });
});
