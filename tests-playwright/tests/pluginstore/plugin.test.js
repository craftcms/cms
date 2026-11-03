/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test('Should show plugin details', async ({page}) => {
  await page.goto('./plugin-store/sherlock');

  await expect(page.getByRole('heading', {level: 1})).toContainText('Sherlock');
  await expect(
    page.getByRole('link', {name: 'PutYourLightsOn', exact: true})
  ).toBeVisible();

  const tabs = page.getByRole('tab', {
    name: /^(Overview|Reviews|Editions|Changelog)$/,
  });
  await expect(tabs).toHaveCount(4);
  await expect(page.getByRole('tab', {name: 'Overview'})).toBeVisible();
  await expect(page.getByRole('tab', {name: 'Changelog'})).toBeVisible();
});

test('Plugin tabs should have their own URLs', async ({page}) => {
  await page.goto('./plugin-store/sherlock');

  await page.getByRole('tab', {name: 'Changelog'}).click();
  await expect(page).toHaveURL(/plugin-store\/sherlock\/changelog$/);
  await expect(
    page.getByRole('heading', {name: /^Version /}).first()
  ).toBeVisible();
});

test('Plugin details should have links to categories', async ({page}) => {
  await page.goto('./plugin-store/sherlock');

  await page
    .getByRole('tabpanel')
    .getByRole('link', {name: 'Security'})
    .click();

  await expect(page.getByRole('heading', {level: 1})).toHaveText('Security');
});
