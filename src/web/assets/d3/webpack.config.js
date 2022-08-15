/*jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const pkgDir = require('pkg-dir');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: require.resolve('d3/build/d3.js'),
          },
          {
            context: pkgDir.sync(require.resolve('d3-format')),
            from: 'locale/*.json',
            to: 'd3-format/',
          },
          {
            context: pkgDir.sync(require.resolve('d3-time-format')),
            from: 'locale/*.json',
            to: 'd3-time-format/',
          },
        ],
      }),
    ],
  },
});
