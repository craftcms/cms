/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {install: './install.js'},
    output: {
      path: __dirname + '/../../../resources/legacy/installer/dist',
    },
  },
});
