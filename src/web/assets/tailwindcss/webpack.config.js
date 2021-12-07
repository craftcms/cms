/* jshint esversion: 6 */
/* globals module, require */
const path = require('path');
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  postCssConfig: path.resolve(__dirname, 'postcss.config.js'),
  config: {
    entry: {'TailwindCss': './TailwindCss.scss'},
  }
});
