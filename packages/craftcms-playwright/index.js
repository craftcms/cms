/* jshint esversion: 9, strict: false */
/* globals module, require */
const base = require('@playwright/test');
const baseConfig = require('./playwright/config/_config');
//const helpers = require('./helpers/generic');
const events = require('./playwright/_events');
const {Setup} = require('@craftcms/playwright/playwright/fixtures/setup');
const {Entry} = require('@craftcms/playwright/playwright/fixtures/entry');

// new way - worker fixture
const test = base.extend({
  craftSetup: [
    async ({browser}, use, workerInfo) => {
      const setup = new Setup();
      await use(setup);
    },
    {scope: 'worker'},
  ],
  craftEntry: [
    async ({browser}, use, workerInfo) => {
      const entry = new Entry();
      await use(entry);
    },
    {scope: 'worker'},
  ],
});

module.exports = {
  getConfig: (config = {}) => {
    return {...baseConfig, ...config};
  },
  test: test,
  expect: base.expect,
  events,
};
