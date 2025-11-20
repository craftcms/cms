/*jshint esversion: 6 */
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
            context: path.resolve(pkgDir.sync(), 'node_modules/d3-format'),
            from: 'locale/*.json',
            to: 'd3-format/',
          },
          {
            context: path.resolve(pkgDir.sync(), 'node_modules/d3-time-format'),
            from: 'locale/*.json',
            to: 'd3-time-format/',
          },
        ],
      }),
    ],
  },
});
