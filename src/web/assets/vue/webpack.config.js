/* jshint esversion: 6 */
/* globals module, require, __dirname */
const path = require('path');
const pkgDir = require('pkg-dir');
const {getConfig} = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

// Resolve a file inside a package, bypassing strict package exports.
const resolve = (pkg, file) =>
  path.join(pkgDir.sync(path.dirname(require.resolve(pkg))), file);

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'vue.js': [
            resolve('vue', 'dist/vue.min.js'),
            resolve('vue-router', 'dist/vue-router.min.js'),
            resolve('vuex', 'dist/vuex.min.js'),
            resolve('vue-autosuggest', 'dist/vue-autosuggest.js'),
          ],
        },
      }),
    ],
  },
});
