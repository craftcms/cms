/* jshint esversion: 6 */
/* globals module, require */
const CraftWebpackConfig = require('@craftcms/webpack/CraftWebpackConfig');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

module.exports = new CraftWebpackConfig({
  config: {
    entry: {},
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'htmx.min.js': [
            require.resolve('htmx.org'),
            require.resolve('./src/htmx.js'),
          ],
        },
      }),
    ],
  }
});
