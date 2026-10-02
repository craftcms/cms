class Entry {
  constructor() {
    this.fieldModifiedText = 'This field has been modified.';
    this.newEntryText = 'This is a new entry.';
    this.editedEntryText = 'This entry has been edited.';
  }

  async waitForAutosaveToComplete(page) {
    await page
      .locator('#revision-indicators')
      .getByTitle('Saving')
      .waitFor({state: 'hidden'});
  }

  async editFirstEntryInElementIndexTable(page) {
    await page.locator('#elements tr:first-child th .label-link').waitFor();
    await page.locator('#elements').getByRole('link', {name: 'Entry'}).click();
    await page.waitForLoadState();
  }

  async editEntryInElementIndexTableByTitle(page, text) {
    await page.locator('#elements tr:first-child th .label-link').waitFor();
    await page.locator('#elements').getByRole('link', {name: text}).click();
    await page.waitForLoadState();
  }

  async switchEntryTypeByName(page, text) {
    await page.locator('#entryType-button').click();
    await page.getByRole('button', {name: text}).click();

    // wait for the loader to disappear
    await this.waitForAutosaveToComplete(page);
  }

  async createEntryBySectionName(page, sectionName) {
    await page.getByLabel('New entry in the ' + sectionName).click();
    await page.waitForLoadState();
  }

  async fillField(page, element, fieldLocator, text, delay = 100) {
    await element.locator(fieldLocator).pressSequentially(text, {delay: delay});

    await this.waitForAutosaveToComplete(page);
  }

  async saveRootEntryAndContinueEditing(page) {
    const url = this.prepElementCreatedEditUrl(page.url());
    await page.keyboard.press('Escape');
    await page.keyboard.press('ControlOrMeta+S');
    await page.waitForURL(url);
    await this.waitForAutosaveToComplete(page);
  }

  async saveRootEntry(page) {
    const url = this.prepElementIndexUrl(page.url());
    await page.getByRole('button', {name: 'Create entry'}).click();
    await page.waitForURL(url);
  }

  prepElementCreatedEditUrl(link) {
    let url = new URL(link);
    return url.origin + url.pathname + '**';
  }

  prepElementIndexUrl(link) {
    let url = new URL(link);
    let pathnameSegments = url.pathname.split('/');
    pathnameSegments.pop();

    return url.origin + pathnameSegments.join('/') + '?**';
  }

  async discardElementChanges(page) {
    page.on('dialog', async (dialog) => {
      await dialog.accept();
    });
    await page.locator('#content .discard-changes-btn').click();
    await page.waitForLoadState();
  }
}

module.exports = {Entry};
