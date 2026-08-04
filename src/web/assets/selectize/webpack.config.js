/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {},
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'selectize.js': [
            require.resolve('@selectize/selectize/dist/js/selectize.js'),
            require.resolve('./src/selectize-plugin-a11y.js'),
          ],
          'css/selectize.css': [
            require.resolve('@selectize/selectize/dist/css/selectize.css'),
          ],
        },
      }),
    ],
  },
});
