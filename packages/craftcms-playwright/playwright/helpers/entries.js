const fieldModifiedText = 'This field has been modified.';
const newEntryText = 'This is a new entry.';
const editedEntryText = 'This entry has been edited.';

const waitForAutosaveToComplete = async (page) => {
  await page.locator('#revision-indicators').getByTitle('Saving').waitFor({state: 'hidden'});
}

const editFirstEntryInElementIndexTable = async (page) => {
  await page.locator("#elements tr:first-child th .label-link").waitFor();
  await page.locator("#elements").getByRole('link', { name: 'Entry' }).click();
  await page.waitForLoadState();
}

module.exports = {
  fieldModifiedText,
  newEntryText,
  editedEntryText,
  waitForAutosaveToComplete,
  editFirstEntryInElementIndexTable
};
