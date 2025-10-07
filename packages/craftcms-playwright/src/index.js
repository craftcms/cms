/* jshint esversion: 9, strict: false */
const base = require('@playwright/test');
const baseConfig = require('./playwright/config/config');
const events = require('./playwright/events');
const {Setup} = require('./playwright/fixtures/setup');
const {Entry} = require('./playwright/fixtures/entry');
const {Matrix} = require('./playwright/fixtures/matrix');
const {Dashboard} = require('./playwright/fixtures/dashboard');
const {PluginStore} = require('./playwright/fixtures/plugin-store');
const logger = require('./playwright/logger');

// new way - worker fixture
const test = base.extend({
  // Craft Setup fixture
  craftSetup: [
    async ({}, use) => {
      const setup = new Setup();
      await use(setup);
    },
    {scope: 'worker'},
  ],

  // Craft Entry fixture
  craftEntry: [
    async ({}, use) => {
      const entry = new Entry();
      await use(entry);
    },
    {scope: 'worker'},
  ],

  // Craft Matrix fixture
  craftMatrix: [
    async ({}, use) => {
      const matrix = new Matrix();
      await use(matrix);
    },
    {scope: 'worker'},
  ],

  // Craft Dashboard fixture
  craftDashboard: async ({page}, use) => {
    const dashboard = new Dashboard(page);
    await use(dashboard);
  },

  // Craft Plugin Store fixture
  craftPluginStore: async ({}, use) => {
    const pluginStore = new PluginStore();
    await use(pluginStore);
  },
});

module.exports = {
  getConfig: (config = {}) => {
    return {...baseConfig, ...config};
  },
  test: test,
  expect: base.expect,
  events,
  logger,
};
