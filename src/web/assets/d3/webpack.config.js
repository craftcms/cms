/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const {join} = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const pkgDir = require('pkg-dir');

module.exports = getConfig({
  context: __dirname,
  watchPaths: [join(__dirname, 'src')],
  config: {
    entry: {
      d3: './index.js',
    },
    module: {
      rules: [
        {
          test: require.resolve('./src/index.js'),
          loader: 'expose-loader',
          options: {
            exposes: [
              {
                globalName: 'd3',
                moduleLocalName: 'default',
              },
            ],
          },
        },
      ],
    },
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
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
