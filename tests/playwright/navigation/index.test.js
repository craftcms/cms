const {test, expect} = require('@craftcms/playwright');

test.beforeEach(async ({page}) => {
  await page.goto('./dashboard');
});

test.describe('Navigation', () => {
  test('Check Items', async ({page, baseURL}) => {
    const navItems = [
      'Entries',
      'Categories',
      'Assets',
      'Users',
      ['Utilities', 'System Report'],
      'Settings',
    ];
    await expect(page.locator('#global-sidebar nav ul li a')).toContainText(
      navItems.map((item) => item)
    );

    for (let i = 0; i < navItems.length; i++) {
      await page.click(
        '#global-sidebar nav ul li a:has-text("' + navItems[i] + '")'
      );
      await expect(page.locator('h1')).toHaveText(navItems[i]);
      await page.goto('./dashboard');
    }
  });
});
