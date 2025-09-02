/* jshint esversion: 9, strict: false */
/* globals module, require */
const base = require('@playwright/test');
const baseConfig = require('./playwright/config/config');
const events = require('./playwright/events');
const {Setup} = require('./playwright/fixtures/setup');
const {Entry} = require('./playwright/fixtures/entry');
const {Dashboard} = require('./playwright/fixtures/dashboard');

// new way - worker fixture
const test = base.extend({
  // Craft Setup fixture
  craftSetup: [
    async ({}, use, workerInfo) => {
      const setup = new Setup();
      await use(setup);
    },
    {scope: 'worker'},
  ],

  // Craft Entry fixture
  craftEntry: [
    async ({}, use, workerInfo) => {
      const entry = new Entry();
      await use(entry);
    },
    {scope: 'worker'},
  ],

  // Craft Dashboard fixture
  craftDashboard: async ({page}, use, workerInfo) => {
    const dashboard = new Dashboard(page);
    await use(dashboard);
  },
});

module.exports = {
  getConfig: (config = {}) => {
    return {...baseConfig, ...config};
  },
  test: test,
  expect: base.expect,
  events,
};
