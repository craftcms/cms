/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      CraftSupportWidget: './CraftSupportWidget.js',
    },
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: './logos',
            to: './logos',
          },
        ],
      }),
    ],
  },
});
