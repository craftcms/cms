/* jshint esversion: 6 */
/* globals module, require, __dirname */
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
            from: path.join(
              pkgDir.sync(require.resolve('axios')),
              'dist/axios.js'
            ),
          },
        ],
      }),
    ],
  },
});
