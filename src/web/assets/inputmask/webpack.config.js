/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const pkgDir = require('pkg-dir');
const path = require('path');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            context: path.join(
              pkgDir.sync(require.resolve('inputmask')),
              'dist'
            ),
            from: './jquery.inputmask.js',
            to: './jquery.inputmask.bundle.js',
          },
        ],
      }),
    ],
  },
});
