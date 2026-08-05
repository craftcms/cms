/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      'component-select-input': './component-select-input.js',
      'cp-compat': './cp-compat.js',
    },
    output: {
      path: __dirname + '/../../../cms-assets/resources/legacy/cpcompat/dist',
    },
  },
});
