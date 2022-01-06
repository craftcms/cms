/* jshint esversion: 6 */
/* globals module, require, __dirname */
const { getConfig } = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');
const glob = require('glob');

const files = glob.sync('lib/*.js');
files.push(...glob.sync('src/Base*.js'), 'src/Garnish.js');
files.push(...glob.sync('src/*.js', {
    ignore: files
}));

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'garnish.js': files
        }
      }),
    ],
  },
});
