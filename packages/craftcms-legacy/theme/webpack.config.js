/* jshint esversion: 6 */
/* globals module, require, webpack */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    output: {
      path: __dirname + '/../../../cms-assets/resources/legacy/theme/dist',
    },
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: './*.css',
            to: '.',
          },
        ],
      }),
    ],
  },
});
