/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      FocalPoint: './FocalPoint.ts',
    },
    output: {
      library: {
        name: 'Craft',
        type: 'assign-properties',
      },
      path: __dirname + '/../../../cms-assets/resources/legacy/focalpoint/dist',
    },
  },
});
