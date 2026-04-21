/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {editsection: './editsection.js'},
    output: {
      path: __dirname + '/../../../resources/legacy/editsection/dist',
    },
  },
});
