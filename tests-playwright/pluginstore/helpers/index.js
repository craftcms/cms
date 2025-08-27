const waitForPluginStore = async ({page}) => {
  // Make sure the Plugin Store is loading its initial state
  const statusMessage = page.locator('.status-message');
  await statusMessage.waitFor({state: 'visible'});

  // Make sure the Plugin Store is done loading its initial state
  await statusMessage.waitFor({state: 'detached'});
};

module.exports = {
  waitForPluginStore,
};
