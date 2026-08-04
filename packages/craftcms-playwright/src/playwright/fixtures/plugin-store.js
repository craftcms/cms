/* jshint esversion: 9, strict: false */
class PluginStore {
  constructor() {}

  async waitForPluginStore(page) {
    // Make sure the Plugin Store is loading its initial state
    const statusMessage = page.locator('.status-message');
    await statusMessage.waitFor({state: 'visible'});

    // Make sure the Plugin Store is done loading its initial state
    await statusMessage.waitFor({state: 'detached'});
  }
}

module.exports = {PluginStore};
