/* jshint esversion: 9, strict: false */
/* globals module, require */
const {test, expect} = require('../../index');

test.beforeEach(async ({craftDashboard}) => {
  await craftDashboard.goTo();
});

test.describe('Navigation', () => {
  const navItems = [
    'Dashboard',
    'Users',
    ['Utilities', 'System Report'],
    'Settings',
    'Plugin Store',
  ];

  test('Global navigation has expected links', async ({page}) => {
    await expect(page.locator('#global-sidebar nav ul li a')).toContainText(
      navItems.map((item) => (Array.isArray(item) ? item[0] : item))
    );
  });

  test('Navigation items go to the correct pages', async ({
    craftDashboard,
    page,
  }) => {
    for (let i = 0; i < navItems.length; i++) {
      await craftDashboard.goTo();
      let text = Array.isArray(navItems[i]) ? navItems[i][0] : navItems[i];
      let title = Array.isArray(navItems[i]) ? navItems[i][1] : text;

      await page.click('#global-sidebar nav ul li a:has-text("' + text + '")');
      await expect(page.locator('h1')).toContainText(title);
    }
  });
});

test.describe('Bypass blocks', () => {
  test('Skip to main is the first element in the focus order', async ({
    craftDashboard,
    page,
  }) => {
    // Focus the first element on the page
    await page.keyboard.press('Tab');
    // Check that the skip link is focused
    const skipLink = page.locator(craftDashboard.skipLink);
    await expect(skipLink).toBeFocused();
    await expect(skipLink).toBeVisible();
    await expect(skipLink).toHaveText('Skip to main section');
  });

  test('Skip link moves focus to the main container', async ({
    craftDashboard,
    page,
  }) => {
    // Focus the first element on the page
    await craftDashboard.skipToMain();
    await expect(
      page.evaluate(() => document.activeElement.id === 'main')
    ).resolves.toBe(true);
  });
});
