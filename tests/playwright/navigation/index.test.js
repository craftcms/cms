const {test, expect} = require('@craftcms/playwright');

test.beforeEach(async ({page}) => {
  await page.goto('./dashboard');
});

test.describe('Navigation', () => {
  test('Check Items', async ({page, baseURL}) => {
    const navItems = [
      'Dashboard',
      'Users',
      ['Utilities', 'System Report'],
      'Settings',
      'Plugin Store',
    ];

    await expect(page.locator('#global-sidebar nav ul li a')).toContainText(
      navItems.map((item) => (Array.isArray(item) ? item[0] : item))
    );

    for (let i = 0; i < navItems.length; i++) {
      await page.goto('./dashboard');
      let text = Array.isArray(navItems[i]) ? navItems[i][0] : navItems[i];
      let title = Array.isArray(navItems[i]) ? navItems[i][1] : text;

      await page.click('#global-sidebar nav ul li a:has-text("' + text + '")');
      await expect(page.locator('h1')).toHaveText(title);
    }
  });
});
