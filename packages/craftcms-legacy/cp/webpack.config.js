/* jshint esversion: 6 */
/* globals module, require, webpack */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      cp: './Craft.js',
    },
    output: {
      path: __dirname + '/../../../cms-assets/resources/legacy/cp/dist',
    },
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: './images/**/*',
            to: '.',
          },
        ],
      }),
    ],
  },
});
