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
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          // Only some of the images are used in the CSS. So we need to make
          // sure we have them all
          {
            from: './images/**/*',
            to: '.',
          },
        ],
      }),
    ],
  },
});
