/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: require.resolve(
              'element-resize-detector/dist/element-resize-detector.min.js'
            ),
            to: 'element-resize-detector.js',
          },
        ],
      }),
    ],
  },
});
