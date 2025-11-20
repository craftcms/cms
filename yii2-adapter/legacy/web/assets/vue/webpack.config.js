/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');
const pkgDir = require('pkg-dir');
const path = require('path');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'vue.js': [
            path.resolve(pkgDir.sync(), 'node_modules/vue/dist/vue.min.js'),
            path.resolve(
              pkgDir.sync(),
              'node_modules/vue-router/dist/vue-router.min.js'
            ),
            path.resolve(pkgDir.sync(), 'node_modules/vuex/dist/vuex.min.js'),
            path.resolve(
              pkgDir.sync(),
              'node_modules/vue-autosuggest/dist/vue-autosuggest.js'
            ),
          ],
        },
      }),
    ],
  },
});
