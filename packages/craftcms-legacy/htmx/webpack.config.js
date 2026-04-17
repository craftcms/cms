/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {},
    output: {
      path: __dirname + '/../../../resources/legacy/htmx/dist',
    },
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'htmx.min.js': [
            require.resolve('htmx.org/dist/htmx.js'),
            require.resolve('./src/htmx.js'),
          ],
        },
      }),
    ],
  },
});
