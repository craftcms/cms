/* jshint esversion: 9, strict: false */
/* globals module, require */
const craftPlaywright = require('@craftcms/playwright');

/**
 * Config for the ddev-based harness (`npx craft-playwright test`), which
 * boots a docker environment and loads Codeception fixtures. The default
 * playwright.config.ts targets the Testbench workbench install instead.
 */
module.exports = craftPlaywright.getConfig();
