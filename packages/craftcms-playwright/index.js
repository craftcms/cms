/* jshint esversion: 9, strict: false */
/* globals module, require */
const base = require('@playwright/test');
const baseConfig = require('./playwright/config/_config');
//const helpers = require('./helpers/generic');
const events = require('./playwright/_events');

module.exports = {
  getConfig: (config = {}) => {
    return {...baseConfig, ...config};
  },
  test: base.test,
  expect: base.expect,
  // helpers,
  events,
};
