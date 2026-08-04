/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    output: {
      path: __dirname + '/../../../cms-assets/resources/legacy/fabric/dist',
    },
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: require.resolve('fabric/dist/fabric.js'),
          },
        ],
      }),
    ],
  },
});
