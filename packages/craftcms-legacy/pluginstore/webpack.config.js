/* jshint esversion: 6 */
/* globals module, require */
const path = require('path');
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  type: 'vue',
  postCssConfig: path.resolve(__dirname, 'postcss.config.js'),
  config: {
    entry: {app: './main.js'},
    output: {
      filename: 'js/app.js',
      chunkFilename: 'js/[name].js',
      path: __dirname + '/../../../resources/legacy/pluginstore/dist',
    },
    module: {
      rules: [
        {
          test: /\.svg$/,
          type: 'asset/source',
        },
      ],
    },
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: './images',
            to: './images',
          },
        ],
      }),
    ],
  },
});
