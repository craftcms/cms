/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {tailwind_reset: './tailwind_reset.js'},
  },
});
