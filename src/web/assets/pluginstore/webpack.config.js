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
