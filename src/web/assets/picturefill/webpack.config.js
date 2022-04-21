/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: require.resolve('picturefill/dist/picturefill.js'),
          },
        ],
      }),
    ],
  },
});
