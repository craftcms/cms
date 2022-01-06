/* jshint esversion: 6 */
/* globals module, require, __dirname */
const { getConfig } = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');
const glob = require('glob');

// chdir so the glob commands work when running from outside this dir
process.chdir(__dirname);

const files = glob.sync('lib/*.js');
files.push('src/Garnish.js', ...glob.sync('src/Base*.js'));
files.push(...glob.sync('src/*.js', {
    ignore: files
}));

module.exports = getConfig({
  context: __dirname,
  config: {
    name: 'garnish',
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'garnish.js': files
        }
      }),
    ],
  },
});
