/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      AssetIndexer: './AssetIndexer.ts',
    },
    output: {
      library: {
        name: 'Craft',
        type: 'assign-properties',
      },
    },
  },
});
