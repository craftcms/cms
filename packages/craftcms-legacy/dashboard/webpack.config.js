/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      Dashboard: './Dashboard.js',
    },
    output: {
      path: __dirname + '/../../../resources/legacy/dashboard/dist',
    },
  },
});
