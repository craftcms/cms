/* jshint esversion: 6 */
/* globals module, require, __dirname */
const { getConfig } = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');
const {join} = require('path');

module.exports = getConfig({
  context: __dirname,
  config: {
    name: 'garnish',
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'garnish.js': [

            // Make paths absolute so this can be run from Craft root as well
            join(__dirname, 'lib/*.js'),
            join(__dirname, 'src/Garnish.js'),
            join(__dirname, 'src/Base*.js'),
            join(__dirname, 'src/!(Garnish|Base*).js')
          ]
        }
      }),
    ],
  },
});
