const waitForPluginStore = async ({page}) => {
  // Make sure the Plugin Store is loading its initial state
  await page.waitForSelector('.status-message', {state: 'visible'});

  // Make sure the Plugin Store is done loading its initial state
  await page.waitForSelector('.status-message', {state: 'detached'});
};

module.exports = {
  waitForPluginStore,
};
