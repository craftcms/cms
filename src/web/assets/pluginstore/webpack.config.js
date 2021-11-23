/* jshint esversion: 6 */
/* globals module, require */
const path = require('path')
const CraftWebpackConfig = require('@craftcms/webpack/CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = new CraftWebpackConfig({
  type: 'vue',
  postCssConfig: path.resolve(__dirname, 'postcss.config.js'),
  config: {
    entry: { app: './main.js'},
    output: {
      filename: 'js/app.js',
      chunkFilename: 'js/[name].js',
    },
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: './images',
            to: './images'
          }
        ]
      }),
    ],
  },
});