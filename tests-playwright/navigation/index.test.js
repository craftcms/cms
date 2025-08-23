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
      await expect(page.locator('h1')).toContainText(title);
    }
  });
});

test.describe('Bypass block', () => {
  test('Skip link should be the first element in the focus order', async ({page, baseURL}) => {
    // Focus the first element on the page
    await page.keyboard.press('Tab');
    // Check that the skip link is focused
    const skipLink = page.locator('a[href="#main"]');
    await expect(skipLink).toBeFocused();
    await expect(skipLink).toBeVisible();
    await expect(skipLink).toHaveText('Skip to main section');
  });

  test('Skip link should move focus to the main container', async ({page, baseURL}) => {
    // Focus the first element on the page
    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter');
    await expect(
      page.evaluate(() => document.activeElement.id === 'main')
    ).resolves.toBe(true);
  });
});
