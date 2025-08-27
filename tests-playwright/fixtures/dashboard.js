const {Page} = require('@craftcms/playwright');

export class DashboardPage {
  constructor(page, baseURL) {
    this.page = page;
    this.baseURL = baseURL;
    this.skipLink = page.locator('a[href="#main"]');
  }

  async goTo() {
    await this.page.goto(`${this.baseURL}/dashboard`);
  }

  async skipToMain() {
    await this.page.keyboard.press('Tab');
    await this.page.keyboard.press('Enter');
  }
}

module.exports = {DashboardPage};
