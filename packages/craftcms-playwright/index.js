/* jshint esversion: 9, strict: false */
/* globals module, require */
const baseConfig = require('./_config');
const helpers = require('./_helpers');
const {test, expect} = require('./_fixtures');

module.exports = {
  getConfig: (config = {}) => {
    return {...baseConfig, ...config};
  },
  helpers,
  test,
  expect,
};
