/* jshint esversion: 9, strict: false */
/* globals module, require */
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