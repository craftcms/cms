/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const pkgDir = require('pkg-dir');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: path.join(
              pkgDir.sync(require.resolve('codemirror')),
              'lib/codemirror.js'
            ),
          },
          {
            from: path.join(
              pkgDir.sync(require.resolve('codemirror')),
              'mode/javascript/javascript.js'
            ),
          },
          {
            from: path.join(
              pkgDir.sync(require.resolve('codemirror')),
              'lib/codemirror.css'
            ),
          },
        ],
      }),
    ],
  },
});
