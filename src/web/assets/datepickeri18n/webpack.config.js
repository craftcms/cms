/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');
const pkgDir = require('pkg-dir');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            context: path.join(
              pkgDir.sync(require.resolve('jquery-ui')),
              'ui',
              'i18n'
            ),
            from: '*',
            to: '.',
          },
        ],
      }),
    ],
  },
});
