/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {transforms: './transforms.js'},
    output: {
      path:
        __dirname + '/../../../cms-assets/resources/legacy/edittransform/dist',
    },
  },
});
