/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {rebrand: './rebrand.js'},
    output: {
      path: __dirname + '/../../../resources/legacy/generalsettings/dist',
    },
  },
});
