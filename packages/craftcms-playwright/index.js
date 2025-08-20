/* jshint esversion: 9, strict: false */
/* globals module, require */
const baseConfig = require('./playwright/config/_config');
const helpers = require('./helpers/generic');
const {test, expect} = require('./_fixtures');
const events = require('./_events');

module.exports = {
  getConfig: (config = {}) => {
    return {...baseConfig, ...config};
  },
  helpers,
  test,
  expect,
  events,
};
