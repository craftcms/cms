/* jshint esversion: 6 */
/* globals module, require, webpack */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const Ttf2Woff2Plugin = require('./Ttf2Woff2Plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      cp: './Craft.js',
    },
    plugins: [
      new Ttf2Woff2Plugin(),
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
