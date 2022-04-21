/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: require.resolve(
              '@benmajor/jquery-touch-events/src/jquery.mobile-events.min.js'
            ),
            to: 'jquery.mobile-events.js',
          },
        ],
      }),
    ],
  },
});
