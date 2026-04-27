/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {Updater: './Updater.js'},
    output: {
      path: __dirname + '/../../../resources/legacy/updater/dist',
    },
  },
});
