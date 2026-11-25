/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: path.resolve(
              process.cwd(),
              'node_modules/codemirror/lib/codemirror.js'
            ),
          },
          {
            from: path.resolve(
              process.cwd(),
              'node_modules/codemirror/mode/javascript/javascript.js'
            ),
          },
          {
            from: path.resolve(
              process.cwd(),
              'node_modules/codemirror/lib/codemirror.css'
            ),
          },
        ],
      }),
    ],
  },
});
