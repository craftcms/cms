/* jshint esversion: 9, strict: false */
class Dashboard {
  constructor(page) {
    this.page = page;
    this.skipLink = 'a[href="#main"]';
  }

  async goTo() {
    await this.page.goto('./dashboard');
  }

  async skipToMain() {
    await this.page.keyboard.press('Tab');
    await this.page.keyboard.press('Enter');
  }
}

module.exports = {Dashboard};
