const {test, expect} = require('@craftcms/playwright');

test.describe('Sections - Page', () => {
  // Make sure we start each test on the settings page
  test.beforeEach(async ({page}) => {
    await page.goto('./settings/sections');
  });

  // Check all the settings groups exist
  test('Check Page', async ({page, baseURL}) => {
    const zilch = page.locator('#content .zilch');
    await expect(zilch).toHaveText('No sections exist yet.');
  });

  // Check New section page loads
  test('New section page', async ({page, baseURL}) => {
    const newButtonSelector = '#header #action-buttons a.submit';
    const newSectionButton = page.locator(newButtonSelector);
    await expect(newSectionButton).toHaveText('New section');
    await page.click(newButtonSelector);
    await expect(page).toHaveURL(
      new URL('./settings/sections/new', baseURL).href
    );

    const fields = [
      page.locator('#content input#name'),
      page.locator('#content input#handle'),
      page.locator('#content input#enableVersioning'),
      page.locator('#content select#type'),
    ];

    for (let i = 0; i < fields.length; i++) {
      await expect(fields[i]).toBeEditable();
    }
  });
});

test.describe('Sections - New', () => {
  // Make sure we start each test on the settings page
  test.beforeEach(async ({page, cleanDb}) => {
    await page.goto('./settings/sections/new');
  });

  test('Create New Channel', async ({page, baseURL}) => {
    await page.fill('#content input#name', 'My New Channel');
    await expect(page.locator('#content input#handle')).toHaveValue(
      'myNewChannel'
    );

    await expect(page.locator('#content select#type')).toHaveValue('channel');

    await page.click(
      '#action-buttons button:has-text("Save and edit entry types")'
    );
    const urlRegExp = new RegExp(/settings\/sections\/\d+?\/entrytypes$/, 'i');
    await expect(page).toHaveURL(urlRegExp);
    await expect(page.locator('h1')).toHaveText('My New Channel Entry Types');

    await expect(page.locator('#tabs div a')).toContainText([
      'Settings',
      'Entry Types',
    ]);
    await expect(
      page.locator('#tabs div a:has-text("Settings")')
    ).not.toHaveClass('sel');
    await expect(
      page.locator('#tabs div a:has-text("Settings")')
    ).toHaveAttribute('aria-selected', 'false');
    await expect(
      page.locator('#tabs div a:has-text("Settings")')
    ).toHaveAttribute('aria-controls', 'settings');
    await expect(
      page.locator('#tabs div a:has-text("Settings")')
    ).toHaveAttribute('role', 'tab');
    await expect(
      page.locator('#tabs div a:has-text("Entry Types")')
    ).toHaveClass('sel');
    await expect(
      page.locator('#tabs div a:has-text("Entry Types")')
    ).toHaveAttribute('aria-selected', 'true');
    await expect(
      page.locator('#tabs div a:has-text("Entry Types")')
    ).toHaveAttribute('aria-controls', 'entryTypes');
    await expect(
      page.locator('#tabs div a:has-text("Entry Types")')
    ).toHaveAttribute('role', 'tab');

    await expect(page.locator('#crumbs nav ul li >> nth=-1 >> a')).toHaveText(
      'My New Channel'
    );
    await expect(
      page.locator('#crumbs nav ul li >> nth=-1 >> a')
    ).toHaveAttribute('href', new RegExp(/settings\/sections\/\d+?$/, 'i'));
  });
});
