import {expect, test} from '@playwright/test';

test('entries index lists seeded posts', async ({page}) => {
  await page.goto('/admin/entries');

  await expect(page.getByRole('heading', {name: 'Entries'})).toBeVisible();
  await expect(page.getByText('Welcome to Craft 6')).toBeVisible();
});

test('settings index renders', async ({page}) => {
  await page.goto('/admin/settings');

  await expect(page.getByRole('heading', {name: 'Settings'})).toBeVisible();
  await expect(page.getByRole('link', {name: 'Sections'})).toBeVisible();
});
