const {test, expect} = require('@craftcms/playwright');

test('Navigate to settings', async ({page}) => {
  await page.goto('./dashboard');
  await page.click('#global-sidebar nav ul li a:has-text("Settings")');
  await expect(page.locator('h1')).toHaveText('Settings');
});

test.describe('Settings', () => {
  // Make sure we start each test on the settings page
  test.beforeEach(async ({page}) => {
    await page.goto('./settings');
  });

  // Check page loads
  test('Check Exists', async ({page, baseURL}) => {
    await expect(page).toHaveURL(new URL('./settings', baseURL).href);
    const title = page.locator('h1');
    await expect(title).toHaveText('Settings');
  });

  // Check all the settings groups exist
  test('Check Page', async ({page, baseURL}) => {
    await expect(page.locator('#content h2')).toContainText([
      'System',
      'Content',
      'Media',
    ]);

    const settingsItems = [
      {text: 'General', slug: 'general'},
      {text: 'Sites', slug: 'sites'},
      {text: 'Routes', slug: 'routes'},
    ];

    for (let i = 0; i < settingsItems.length; i++) {
      await page.click(
        '#content li a:has-text("' + settingsItems[i].text + '")'
      );
      await expect(page).toHaveURL(
        new URL('./settings/' + settingsItems[i].slug, baseURL).href
      );
      await page.goto('./settings');
    }
  });
});
