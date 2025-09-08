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
}

module.exports = {Entry};
