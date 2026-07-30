/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

// Prism ships here as a prebuilt, vendored bundle (a custom prismjs.com build —
// see the banner in src/prism.js for the language/plugin set) rather than an npm
// dependency, so this config just copies it into the legacy dist output that
// PrismJsAsset points at.
module.exports = getConfig({
  context: __dirname,
  config: {
    output: {
      path: __dirname + '/../../../cms-assets/resources/legacy/prismjs/dist',
    },
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {from: path.join(__dirname, 'src/prism.js')},
          {from: path.join(__dirname, 'src/prism.css')},
        ],
      }),
    ],
  },
});
