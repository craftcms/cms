/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: require.resolve('punycode/punycode.js'),
          },
        ],
      }),
    ],
  },
});
