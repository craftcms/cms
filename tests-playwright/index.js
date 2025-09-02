/* jshint esversion: 9, strict: false */
/* globals module, require */
const craftPlaywright = require('@craftcms/playwright');
const {PluginStore} = require('./fixtures/plugin-store');

craftPlaywright.test = craftPlaywright.test.extend({
  // Here there is the ability to extend the test object
  craftPluginStore: async ({}, use) => {
    const pluginStore = new PluginStore();
    await use(pluginStore);
  }
});

// You can listen to events here
// craftPlaywright.events.cleanAll.on('before', async () => {
//   process.stdout.write('--- Before Clean All --- \n');
// });

module.exports = craftPlaywright;